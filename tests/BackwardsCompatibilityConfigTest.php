<?php

namespace Rollbar;

class BackwardsCompatibilityConfigTest extends BaseRollbarTest
{
    public function testConfigValues()
    {
        Rollbar::init(array(
            'access_token' => $this->getTestAccessToken(),
            'agent_log_location' => '/var/log/rollbar-php',
            'base_api_url' => 'http://dev:8090/api/1/',
            'batch_size' => 50,
            'batched' => true,
            'branch' => 'other',
            'capture_error_stacktraces' => true,
            'checkIgnore' => function () {
                $check = isset($_SERVER['HTTP_USER_AGENT']) &&
                    strpos($_SERVER['HTTP_USER_AGENT'], 'Baiduspider') !== false;
                if ($check) {
                    // ignore baidu spider
                    return true;
                }

                // no other ignores
                return false;
            },
            'code_version' => '1.2.3',
            'environment' => 'production',
            'error_sample_rates' => array(
                E_WARNING => 0.5,
                E_ERROR => 1
            ),
            'handler' => 'blocking',
            'host' => 'my_host',
            'include_error_code_context' => true,
            'included_errno' => E_ERROR | E_WARNING,
            'logger' => new FakeLog(),
            'person' => array(
                'id' => "1",
                'username' => 'test-user',
                'email' => 'test@rollbar.com'
            ),
            'person_fn' => function () {
                return array(
                    'id' => "1",
                    'username' => 'test-user',
                    'email' => 'test@rollbar.com'
                );
            },
            'root' => '/Users/brian/www/app',
            'scrub_fields' => array('test'),
            'timeout' => 10,
            'report_suppressed' => true,
            'use_error_reporting' => true,
            'proxy' => array(
                'address' => '127.0.0.1:8080',
                'username' => 'my_user',
                'password' => 'my_password'
            )
        ));
    }
}
