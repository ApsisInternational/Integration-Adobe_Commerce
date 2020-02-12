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
            ->buildBody([
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret
            ]);
        return $this->processResponse($this->execute(), __METHOD__);
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
        return $this->processResponse($this->execute(), __METHOD__);
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
        return $this->processResponse($this->execute(), __METHOD__);
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
        return $this->processResponse($this->execute(), __METHOD__);
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
        return $this->processResponse($this->execute(), __METHOD__);
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
        return $this->processResponse($this->execute(), __METHOD__);
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
        return $this->processResponse($this->execute(), __METHOD__);
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
            ->buildBody($attributes);
        return $this->processResponse($this->execute(), __METHOD__);
    }

    /**
     *  Get all profile attributes
     *
     * @param string $keySpaceDiscriminator
     * @param string $profileKey
     * @param string $sectionDiscriminator
     *
     * @return bool|stdClass
     */
    public function getProfileAttributes(
        string $keySpaceDiscriminator,
        string $profileKey,
        string $sectionDiscriminator
    ) {
        $url = self::HOST_NAME . '/audience/keyspaces/' . $keySpaceDiscriminator . '/profiles/' . $profileKey .
            '/sections/' . $sectionDiscriminator . '/attributes';
        $this->setUrl($url)
            ->setVerb(Rest::VERB_GET);
        return $this->processResponse($this->execute(), __METHOD__);
    }

    /**
     * Get all profile events
     *
     * @param string $keySpaceDiscriminator
     * @param string $profileKey
     * @param string $sectionDiscriminator
     * @param array $typeIds
     *
     * @return bool|stdClass
     */
    public function getProfileEvents(
        string $keySpaceDiscriminator,
        string $profileKey,
        string $sectionDiscriminator,
        array $typeIds = []
    ) {
        $url = self::HOST_NAME . '/audience/keyspaces/' . $keySpaceDiscriminator . '/profiles/' . $profileKey .
            '/sections/' . $sectionDiscriminator . '/events';
        $this->setUrl($url)
            ->setVerb(Rest::VERB_GET);

        if (! empty($typeIds)) {
            $this->buildBody(['typeID' => $typeIds]);
        }

        return $this->processResponse($this->execute(), __METHOD__);
    }

    /**
     * Subscribe profile to topic
     *
     * @param string $keySpaceDiscriminator
     * @param string $profileKey
     * @param string $sectionDiscriminator
     * @param string $consentListDiscriminator
     * @param string $topicDiscriminator
     *
     * @return bool|stdClass
     */
    public function subscribeProfileToTopic(
        string $keySpaceDiscriminator,
        string $profileKey,
        string $sectionDiscriminator,
        string $consentListDiscriminator,
        string $topicDiscriminator
    ) {
        $url = self::HOST_NAME . '/audience/keyspaces/' . $keySpaceDiscriminator . '/profiles/' . $profileKey .
            '/sections/' . $sectionDiscriminator . '/subscriptions';
        $this->setUrl($url)
            ->setVerb(Rest::VERB_POST)
            ->buildBody(
                [
                    'consent_list_discriminator' => $consentListDiscriminator,
                    'topic_discriminator' => $topicDiscriminator
                ]
            );
        return $this->processResponse($this->execute(), __METHOD__);
    }

    /**
     * Create consent
     *
     * @param string $channelDiscriminator
     * @param string $address
     * @param string $sectionDiscriminator
     * @param string $consentListDiscriminator
     * @param string $topicDiscriminator
     * @param string $type
     *
     * @return bool|stdClass
     */
    public function createConsent(
        string $channelDiscriminator,
        string $address,
        string $sectionDiscriminator,
        string $consentListDiscriminator,
        string $topicDiscriminator,
        string $type
    ) {
        $url = self::HOST_NAME . '/audience/channels/' . $channelDiscriminator . '/addresses/' . $address . '/consents';
        $this->setUrl($url)
            ->setVerb(Rest::VERB_POST)
            ->buildBody(
                [
                    'section_discriminator' => $sectionDiscriminator,
                    'consent_list_discriminator' => $consentListDiscriminator,
                    'topic_discriminator' => $topicDiscriminator,
                    'type' => $type
                ]
            );
        return $this->processResponse($this->execute(), __METHOD__);
    }

    /**
     * Get all events types
     *
     * @param string $sectionDiscriminator
     *
     * @return bool|stdClass
     */
    public function getEventsTypes(string $sectionDiscriminator)
    {
        $this->setUrl(self::HOST_NAME . '/audience/sections/' . $sectionDiscriminator . '/events')
            ->setVerb(Rest::VERB_GET);
        return $this->processResponse($this->execute(), __METHOD__);
    }

    /**
     * Posting Events to a Profile
     *
     * @param string $keySpaceDiscriminator
     * @param string $profileKey
     * @param string $sectionDiscriminator
     * @param array $events
     *
     * @return bool|stdClass
     */
    public function postEventsToProfile(
        string $keySpaceDiscriminator,
        string $profileKey,
        string $sectionDiscriminator,
        array $events
    ) {
        $url = self::HOST_NAME . '/audience/keyspaces/' . $keySpaceDiscriminator . '/profiles/' . $profileKey .
            '/sections/' . $sectionDiscriminator . '/events';
        $this->setUrl($url)
            ->setVerb(Rest::VERB_POST)
            ->buildBody(['items' => $events]);
        return $this->processResponse($this->execute(), __METHOD__);
    }

    /**
     * @param null|stdClass $response
     * @param string $method
     *
     * @return boolean|stdClass
     */
    private function processResponse($response, string $method)
    {
        if ($this->curlError) {
            return false;
        }
        /** Todo handle all error cases */
        if (isset($response->detail)) {
            $this->helper->debug($method, $this->getErrorArray($response));
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
            'status' => $response->status,
            'title' => $response->title,
            'detail' => $response->detail
        ];
    }
}
