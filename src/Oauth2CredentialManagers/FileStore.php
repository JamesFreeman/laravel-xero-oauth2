<?php

namespace Webfox\Xero\Oauth2CredentialManagers;

use Illuminate\Filesystem\FilesystemManager;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Webfox\Xero\Exceptions\XeroCredentialsNotFound;
use Webfox\Xero\OauthCredentialManager;

class FileStore extends BaseCredentialManager implements OauthCredentialManager
{
    /** @var FilesystemManager */
    protected $disk;

    /** @var string */
    protected string $filePath;

    public function __construct()
    {
        $this->disk = app(FilesystemManager::class)->disk(config('xero.credential_disk', config('filesystems.default')));
        $this->filePath = 'xero.json';

        parent::__construct();
    }

    public function exists(): bool
    {
        return $this->disk->exists($this->filePath);
    }

    public function store(AccessTokenInterface $token, array $tenants = null): void
    {
        $ret = $this->disk->put($this->filePath, json_encode([
            'token' => $token->getToken(),
            'refresh_token' => $token->getRefreshToken(),
            'id_token' => $token->getValues()['id_token'],
            'expires' => $token->getExpires(),
            'tenants' => $tenants ?? $this->getTenants(),
        ]), 'private');

        if ($ret === false) {
            throw new \Exception("Failed to write to file: {$this->filePath}");
        }
    }

    protected function data(string $key = null)
    {
        if (! $this->exists()) {
            throw new XeroCredentialsNotFound('Xero oauth credentials are missing');
        }

        $cacheData = json_decode($this->disk->get($this->filePath), true);

        return empty($key) ? $cacheData : ($cacheData[$key] ?? null);
    }
}
