<?php

namespace Perevorot\Users\Classes;

use RainLab\User\Classes\AuthManager as BaseAuthManager;
use League\OAuth2\Client\Token\AccessToken;
use Perevorot\Users\Models\User;
use Perevorot\Users\Models\UserGroup;
use Perevorot\Users\Models\ExternalAuth;
use ApplicationException;
use Redirect;
use Session;
use Request;
use Input;
use Perevorot\Users\Facades\Auth;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class AuthManager extends BaseAuthManager
{
    protected $userModel='Perevorot\Users\Models\User';

    protected $groupModel='Perevorot\Users\Models\UserGroup';
        
    public function getUser()
    {
        $settings=ExternalAuth::instance();
        //$is_post=request()->isMethod('post');

        if(!$this->user && $settings->is_enabled)
        {
            foreach(['clientId', 'clientSecret', 'urlAuthorize', 'urlAccessToken', 'urlResourceOwnerDetails', 'urlProfile'] as $var)
            {
                if(empty($settings->{$var}))
                    throw new ApplicationException('OAUTH2 error: `'.$var.'` должно быть заполнено');
            }

            $provider=new \League\OAuth2\Client\Provider\GenericProvider([
                'clientId'                => $settings->clientId,
                'clientSecret'            => $settings->clientSecret,
                'urlAuthorize'            => $settings->urlAuthorize,
                'urlAccessToken'          => $settings->urlAccessToken,
                'urlResourceOwnerDetails' => $settings->urlResourceOwnerDetails,
                'redirectUri'             => Request::url(),
            ]);

            $accessToken=Session::get('accessToken');
            $accessCode=Input::get('code');

            if (!$accessToken || $accessCode)
            {
                if(!$accessCode)
                {
                    if(empty($settings->is_nologin))
                    {
                        $authorizationUrl=$provider->getAuthorizationUrl();
    
                        header('Location: '.$authorizationUrl);
                        exit;
                    }
                }
                else
                {
                    try
                    {
                        $accessToken=$provider->getAccessToken('authorization_code', [
                            'code' => $accessCode
                        ]);

                        Session::put('accessToken', $accessToken);
                    }
                    catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
                        throw new ApplicationException('OAUTH2 error: '.$e->getMessage());
                    }                
                }
            }

            if($accessToken && $accessToken->hasExpired())
            {
                $refreshToken=$accessToken->getRefreshToken();
                
                try
                {
                    $newToken=$provider->getAccessToken('refresh_token', [
                        'refresh_token' => $refreshToken
                    ]);
                }
                catch(IdentityProviderException $e)
                {
                    if($e->getMessage()=='invalid_grant')
                    {
                        $authorizationUrl=$provider->getAuthorizationUrl();

                        header('Location: '.$authorizationUrl);
                        exit;
                    }
                }

                $newAccessToken=new AccessToken([
                    'access_token'=>$newToken->getToken(),
                    'resource_owner_id'=>$newToken->getResourceOwnerId(),
                    'refresh_token'=>$refreshToken,
                    'expires'=>$newToken->getExpires(),
                    'token_type' => 'Bearer',
                    'scope' => 'basic'
                ]);

                Session::put('accessToken', $newAccessToken);
                $accessToken=$newAccessToken;
            }
    
            if($accessToken)
            {
                try
                {
                    $resourceOwner=$provider->getResourceOwner($accessToken);
                }
                catch(IdentityProviderException $e)
                {
                    if($e->getMessage()=='unauthorized_request')
                    {
                        $authorizationUrl=$provider->getAuthorizationUrl();

                        header('Location: '.$authorizationUrl);
                        exit;
                    }
                }

                $owner=$resourceOwner->toArray();

                $this->user=$this->externalUser($owner);
                $this->user->afterFetch();
            }
        }

        if (is_null($this->user)) {
            $this->check();
        }

        return $this->user;
    }
    
    private function externalUser($owner)
    {
        $password=str_random(10);

        $user=User::firstOrNew([
            'username'=>$owner['username']
        ]);

        $user->username=$owner['username'];
        $user->email=$owner['email'];
        $user->password=$password;
        $user->is_activated=1;
        $user->activated_at=date('Y-m-d H:i:s');

        $data=[];

        foreach($owner as $k=>$one)
        {
            if(in_array($k, $user->dynamicFields))
                $data[$k]=$one;
        }

        $user->processFields($data);
        $user->validate();
        $user->save();
        $user->exportFieldsMutators();

        $roles=[
            'customer'=>$owner['is_group_customer'],
            'supplier'=>$owner['is_group_supplier']
        ];

        if(!empty(array_filter(array_values($roles))))
        {
            $group_ids=UserGroup::select('id')->whereIn('code', array_keys($roles))->get();
            $group_ids=array_pluck($group_ids, 'id');

            $user->groups()->sync($group_ids);
        }
        else
            $user->groups()->sync([]);

        return $user;
    }
}
