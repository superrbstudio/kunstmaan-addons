<?php

namespace Superrb\KunstmaanAddonsBundle;

use Kunstmaan\AdminBundle\Helper\DomainConfigurationInterface;

class Site
{
    /**
     * @var DomainConfigurationInterface
     */
    private $domainConfiguration;

    /**
     * @var string[]
     */
    private $allowedHosts;

    /**
     * @var string[]
     */
    private $host;

    /**
     * @var string|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $mailchimpListId;

    /**
     * @var string[]|null
     */
    private $emailSender;

    /**
     * @var array
     */
    private $emailSenders;

    /**
     * @var string[]|null
     */
    private $googleAnalyticsIds;

    /**
     * @var string|null
     */
    private $googleAnalyticsId;

    public function __construct(
        DomainConfigurationInterface $domainConfiguration,
        array $allowedHosts,
        array $mailchimpListIds,
        array $emailSenders,
        array $googleAnalyticsIds
    ) {
        $this->allowedHosts        = $allowedHosts;
        $this->domainConfiguration = $domainConfiguration;
        $this->emailSenders        = $emailSenders;
        $this->googleAnalyticsIds  = $googleAnalyticsIds;

        $this->host = $this->domainConfiguration->getFullHost($this->domainConfiguration->getHost());

        if ($this->host) {
            $this->id   = $this->host['id'];
        }

        if ('' === $this->id) {
            $this->id = null;
        }

        if (isset($mailchimpListIds[$this->id])) {
            $this->mailchimpListId = $mailchimpListIds[$this->id];
        }

        if (isset($emailSenders[$this->id])) {
            $this->emailSender = $emailSenders[$this->id];
        } else {
            $this->emailSender = $emailSenders['default'];
        }

        if (isset($googleAnalyticsIds[$this->id])) {
            $this->googleAnalyticsId = $googleAnalyticsIds[$this->id];
        }
    }

    public function getDomainConfiguration(): DomainConfigurationInterface
    {
        return $this->domainConfiguration;
    }

    /**
     * @return string[]
     */
    public function getAllowedHosts(): array
    {
        return $this->allowedHosts;
    }

    /**
     * @return string[]
     */
    public function getHost(): array
    {
        return $this->host;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getMailchimpListId(): ?string
    {
        return $this->mailchimpListId;
    }

    public function getEmailSender(?string $siteId = null): ?array
    {
        if ($siteId) {
            return $this->emailSenders[$siteId];
        }

        return $this->emailSender;
    }

    public function getGoogleAnalyticsId(): ?string
    {
        return $this->googleAnalyticsId;
    }

    public function getTranslationDomain(): ?string
    {
        return $this->getId();
    }
}
