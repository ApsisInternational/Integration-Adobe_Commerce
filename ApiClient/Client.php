<?php

namespace Apsis\One\ApiClient;

use stdClass;

class Client extends Rest
{
    const HOST_NAME = 'https://api.apsis.one';

    /**
     * Get access token
     *
     * @param string $clientId
     * @param string $clientSecret
     *
     * @return bool|stdClass
     */
    public function getAccessToken(string $clientId, string $clientSecret)
    {
        $this->setUrl(self::HOST_NAME . '/oauth/token')
            ->setVerb(Rest::VERB_POST)
            ->buildPostBody([
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret
            ]);
        return $this->processResponse($this->execute());
    }

    /**
     * Get all registered key spaces
     *
     * @return bool|stdClass
     */
    public function getKeySpaces()
    {
        $this->setUrl(self::HOST_NAME . '/audience/keyspaces')
            ->setVerb(Rest::VERB_GET);
        return $this->processResponse($this->execute());
    }

    /**
     * Get all available communication channels
     *
     * @return bool|stdClass
     */
    public function getChannels()
    {
        $this->setUrl(self::HOST_NAME . '/audience/channels')
            ->setVerb(Rest::VERB_GET);
        return $this->processResponse($this->execute());
    }

    /**
     * Get all sections on the APSIS One account
     *
     * @return bool|stdClass
     */
    public function getSections()
    {
        $this->setUrl(self::HOST_NAME . '/audience/sections')
            ->setVerb(Rest::VERB_GET);
        return $this->processResponse($this->execute());
    }

    /**
     * Get all attributes within a specific section
     *
     * @param string $sectionDiscriminator
     *
     * @return bool|stdClass
     */
    public function getAttributes(string $sectionDiscriminator)
    {
        $this->setUrl(self::HOST_NAME . '/audience/sections/' . $sectionDiscriminator . '/attributes')
            ->setVerb(Rest::VERB_GET);
        return $this->processResponse($this->execute());
    }

    /**
     * Get all Consent lists within a specific section
     *
     * @param string $sectionDiscriminator
     *
     * @return bool|stdClass
     */
    public function getConsentLists(string $sectionDiscriminator)
    {
        $this->setUrl(self::HOST_NAME . '/audience/sections/' . $sectionDiscriminator . '/consent-lists')
            ->setVerb(Rest::VERB_GET);
        return $this->processResponse($this->execute());
    }

    /**
     * Get all topics on a consent list
     *
     * @param string $sectionDiscriminator
     * @param string $consentListDiscriminator
     *
     * @return bool|stdClass
     */
    public function getTopics(string $sectionDiscriminator, string $consentListDiscriminator)
    {
        $this->setUrl(
            self::HOST_NAME . '/audience/sections/' . $sectionDiscriminator . '/consent-lists/' .
            $consentListDiscriminator . '/topics'
        )->setVerb(Rest::VERB_GET);
        return $this->processResponse($this->execute());
    }

    /**
     * Store a set of attribute values on a profile
     *
     * @param string $keySpaceDiscriminator
     * @param string $profileKey
     * @param string $sectionDiscriminator
     * @param array $attributes
     *
     * @return bool|stdClass
     */
    public function createProfile(
        string $keySpaceDiscriminator,
        string $profileKey,
        string $sectionDiscriminator,
        array $attributes
    ) {
        $url = self::HOST_NAME . '/audience/keyspaces/' . $keySpaceDiscriminator . '/profiles/' . $profileKey .
            '/sections/' . $sectionDiscriminator . '/attributes';
        $this->setUrl($url)
            ->setVerb(Rest::VERB_PATCH)
            ->buildPostBody($attributes);
        return $this->processResponse($this->execute());
    }

    /**
     * Subscribe profile to topic
     *
     * @param string $keyspaceDiscriminator
     * @param string $profileKey
     * @param string $sectionDiscriminator
     * @param string $consentListDiscriminator
     * @param string $topicDiscriminator
     *
     * @return bool|stdClass
     */
    public function subscribeProfileToTopic(
        string $keyspaceDiscriminator,
        string $profileKey,
        string $sectionDiscriminator,
        string $consentListDiscriminator,
        string $topicDiscriminator
    ) {
        $url = self::HOST_NAME . '/audience/keyspaces/' . $keyspaceDiscriminator . '/profiles/' . $profileKey .
            '/sections/' . $sectionDiscriminator . '/subscriptions';
        $this->setUrl($url)
            ->setVerb(Rest::VERB_POST)
            ->buildPostBody(
                [
                    'consent_list_discriminator' => $consentListDiscriminator,
                    'topic_discriminator' => $topicDiscriminator
                ]
            );
        return $this->processResponse($this->execute());
    }

    /**
     * @param null|stdClass $response
     *
     * @return boolean|stdClass
     */
    private function processResponse($response)
    {
        if ($this->curlError) {
            return false;
        }
        /** Todo handle all error cases */
        if (isset($response->message)) {
            $this->helper->debug(__METHOD__, $this->getErrorArray($response));
            return false;
        }

        return $response;
    }

    /**
     * @param stdClass $response
     * @return array
     */
    private function getErrorArray($response)
    {
        return $data = [
            'code' => $response->status_code,
            'description' => $response->message
        ];
    }
}