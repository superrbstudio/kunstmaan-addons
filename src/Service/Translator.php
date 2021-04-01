<?php

namespace Superrb\KunstmaanAddonsBundle\Service;

use App\Kernel;
use Closure;
use Kunstmaan\TranslatorBundle\Repository\TranslationRepository;
use Kunstmaan\TranslatorBundle\Service\Translator\Loader;
use Kunstmaan\TranslatorBundle\Service\Translator\Translator as KunstmaanTranslator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Superrb\KunstmaanAddonsBundle\Site;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Formatter\MessageFormatterInterface;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\MessageCatalogue;

class Translator extends KunstmaanTranslator
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @param mixed  $resourceCacher
     * @param string $defaultLocale
     * @param bool   $profilerEnable
     */
    public function __construct(
        ContainerInterface $container,
        Site $site,
        MessageFormatterInterface $formatter,
        RequestStack $requestStack,
        KernelInterface $kernel,
        TranslationRepository $translationRepository,
        $resourceCacher,
        $defaultLocale = null,
        array $loaderIds = [],
        array $options = [],
        $profilerEnable = false
    ) {
        $this->site    = $site;
        $this->request = $requestStack->getMasterRequest();
        $this->kernel  = $kernel;

        parent::__construct($container, $formatter, $defaultLocale, $loaderIds, $options, $profilerEnable);

        $this->setTranslationRepository($translationRepository);
        $this->setResourceCacher($resourceCacher);

        $this->loadTranslations($translationRepository);
    }

    protected function loadTranslations(TranslationRepository $translationRepository)
    {
        $loader = new Loader();
        $loader->setTranslationRepository($translationRepository);
        $this->addLoader('database', $loader);

        $loader = new YamlFileLoader();
        $this->addLoader('yaml', $loader);
        $this->addLoader('yml', $loader);
        $path = $this->kernel->getProjectDir().'/translations';

        $loader = new XliffFileLoader();
        $this->addLoader('xlf', $loader);
        $this->addLoader('xliff', $loader);

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($files as $file) {
            dump($file);
        }
        exit;
        $this->addResource('yaml', $path.'/translations/messages'.MessageCatalogue::INTL_DOMAIN_SUFFIX.'.en.yaml', 'en', 'messages'.MessageCatalogue::INTL_DOMAIN_SUFFIX);
        $this->addResource('yaml', $path.'/translations/retainerclub'.MessageCatalogue::INTL_DOMAIN_SUFFIX.'.en.yaml', 'en', 'retainerclub'.MessageCatalogue::INTL_DOMAIN_SUFFIX);
        $this->addResource('yaml', $path.'/translations/mouthguardclub'.MessageCatalogue::INTL_DOMAIN_SUFFIX.'.en.yaml', 'en', 'mouthguardclub'.MessageCatalogue::INTL_DOMAIN_SUFFIX);

        $dirs = [];
        if (class_exists('Symfony\Component\Validator\Validation')) {
            $r = new \ReflectionClass('Symfony\Component\Validator\Validation');

            $dirs[] = dirname($r->getFilename()).'/Resources/translations';
        }
        if (class_exists('Symfony\Component\Form\Form')) {
            $r = new \ReflectionClass('Symfony\Component\Form\Form');

            $dirs[] = dirname($r->getFilename()).'/Resources/translations';
        }

        if (Kernel::VERSION_ID < 4000) {
            $overridePath = $this->kernel->getRootDir().'/Resources/%s/translations';
        } else {
            $overridePath = $this->kernel->getProjectDir().'/translations';
        }

        foreach ($this->kernel->getBundles() as $bundle => $class) {
            $reflection = new \ReflectionClass($class);
            if (is_dir($dir = dirname($reflection->getFilename()).'/Resources/translations')) {
                $dirs[] = $dir;
            }
            if (is_dir($dir = sprintf($overridePath, $bundle))) {
                $dirs[] = $dir;
            }
        }

        if (Kernel::VERSION_ID < 4000) {
            $dir = $this->kernel->getRootDir().'/Resources/translations';
        } else {
            $dir = $this->kernel->getProjectDir().'/translations';
        }

        if (is_dir($dir)) {
            $dirs[] = $dir;
        }

        // Register translation resources
        if (count($dirs) > 0) {
            $this->addDirectoryResources($dirs);
        }

        $this->addDatabaseResources();
    }

    /**
     * @param string[] $dirs
     */
    protected function addDirectoryResources(array $dirs)
    {
        $finder = Finder::create();
        $finder->files();

        $finder->filter(
            function (\SplFileInfo $file) {
                return 2 === substr_count($file->getBasename(), '.') && preg_match('/\.\w+$/', $file->getBasename());
            }
        );

        $finder->in($dirs);

        foreach ($finder as $file) {
            // filename is domain.locale.format
            list($domain, $locale, $format) = explode('.', $file->getBasename());
            $this->addResource($format, (string) $file, $locale, $domain);
        }
    }

    /**
     * @param string $id
     * @param string $domain
     * @param string $locale
     */
    public function trans($id, array $parameters = [], $domain = 'messages', $locale = 'en'): string
    {
        if ($id instanceof Closure) {
            return $id();
        }
        if ('messages' === $domain || null === $domain) {
            $domain = $this->site->getTranslationDomain();
        }

        if ($value = $this->performTranslation($id, $domain, $parameters, $locale)) {
            return $value;
        }

        if ($value = $this->performTranslation($id, 'messages', $parameters, $locale)) {
            return $value;
        }

        $pseudoValue = array_pop(explode('.', $id));

        return u($pseudoValue)->title();
    }

    /**
     * @param string $locale
     */
    public function performTranslation(string $id, string $domain, array $parameters = [], $locale = 'en'): ?string
    {
        $translated = parent::trans($id, $parameters, $domain, $locale);

        if ($translated !== $id && $translated !== str_replace(array_keys($parameters), array_values($parameters), $id)) {
            return $translated;
        }

        return null;
    }
}
