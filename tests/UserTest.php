<?php

use App\Models\User;
use App\Mail\EmailVerify;
use App\Support\Facades\Captcha;
use Illuminate\Support\Facades\Mail;

class UserTest extends TestCase
{
    protected function getCreateData($username)
    {
        return [
            'username' => "test_${username}",
            'password' => 'pwpwpwpw',
            'email' => 'test@example.org',
            'birthDate' => '1975-01-01',
            'tosAccept' => true,
            'captchaToken' => 'test',
        ];
    } // end getCreateData

    public function testSignup()
    {
        Captcha::shouldReceive('verify')
            ->once()
            ->with('test')
            ->andReturn(new class {
                public function isSuccess()
                {
                    return true;
                }
            });

        $resp = $this->json('PUT', '/user', $this->getCreateData(__FUNCTION__));

        $resp->assertResponseOk();
        $resp->seeJsonStructure(['id', 'username']);
        Mail::assertSent(EmailVerify::class);
    } // end testSignup

    public function testSignupWithBadCaptcha()
    {
        Captcha::shouldReceive('verify')
            ->once()
            ->with('test')
            ->andReturn(new class {
                public function isSuccess()
                {
                    return false;
                }
            });

        $resp = $this->json('PUT', '/user', $this->getCreateData(__FUNCTION__));

        $resp->assertResponseStatus(400);
        $resp->seeJsonStructure(['errors' => ['fields' => ['captchaToken']]]);
    } // end testSignup

    public function testGetSelf()
    {
        $user = factory('App\Models\User')->create();
        $resp = $this->actingAs($user)->json('GET', '/user');

        $resp->assertResponseOk();
        $resp->seeJsonStructure(['id', 'username']);
    } // end testGetSelf

    public function testGetOtherUser()
    {
        $profile_user = factory('App\Models\User')->create();
        $profile_id = $profile_user->id;

        $auth_user = factory('App\Models\User')->create();
        $resp = $this->actingAs($auth_user)->json('GET', "/user/$profile_id");

        $resp->assertResponseOk();
        $resp->seeJsonStructure(['id', 'username']);
    } // end testGetOtherUser

    public function testUpdateProfileEmail()
    {
        $user = factory('App\Models\User')->create();
        $resp = $this->actingAs($user)->json('POST', '/user', [
            'email' => 'something-different@example.org',
        ]);

        $resp->assertResponseOk();
        $resp->seeJsonStructure(['id', 'username']);

        $user = User::where('id', $user->id)->first();
        $this->assertFalse($user->email_confirmed);
        $this->assertNotNull($user->email_confirmation_sent);
        
        Mail::assertSent(EmailVerify::class);
    } // end testUpdateProfileEmail

    public function testUpdateProfilePassword()
    {
        $user = factory('App\Models\User')->create();
        $pw_hash_orig = $user->password_hash;

        $resp = $this->actingAs($user)->json('POST', '/user', [
            'password' => 'literallydogs',
        ]);

        $resp->assertResponseOk();
        $resp->seeJsonStructure(['id', 'username']);

        $user = User::find($user->id);
        $this->assertNotEquals($pw_hash_orig, $user->pw_hash);
    } // end testUpdateProfilePassword
} // end UserTest
