<?php

class auth {

    protected $app;
    protected $module;

    protected $attempt;
    protected $user;

    protected $userObj;

    protected $isLogged = false;

    public function __construct($app, $module) {

        $this->app = $app;
        $this->module = $module;

        $this->attempt = new authAttempt($app, $module);

        return $this;

    }

    public function login($email, $password, $remember = 0) {

        if($block = $this->attempt->isBlocked()) {
            $this->app->message->add(__('Login blocked for %s min %s sec', [$block['minutes'], $block['seconds']]), 'error');
            return [
                'code' => 401
            ];
        }

        $validateEmail = $this->validateEmail($email);

        if($validateEmail['error']) {
            $this->attempt->add();
            $this->app->message->add($validateEmail['message'], 'error');
            return [
                'code' => 401
            ];
        }

        $userID = $this->app->user->getID(strtolower($email));

        if(!$userID) {
            $this->attempt->add();
            $this->app->message->add('Email not found', 'error');
            return [
                'code' => 401
            ];
        }

        $user = $this->app->user->getUser($userID);

        if(!$this->passwordRehash($password, $user['password'], $userID)) {
            $this->attempt->add();
            $this->app->message->add('Incorrect password', 'error');
            return [
                'code' => 401
            ];
        }

        $session = $this->addSession($user['id'], $remember);

        $this->app->hook->do_action('auth.login', $this->app, $this);

        return true;

    }

    public function logout() {
        $hash = $this->getSessionHash();
        if(strlen($hash) != 40) {
            return false;
        }
        return $this->deleteSession($hash);
    }

    protected function addSession($userID, $remember) {

        $user = $this->app->user->getUser($userID);
        if(!$user) {
            return false;
        }

        $data['hash'] = sha1($this->module->config('session')['key'].microtime());
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $this->deleteExistingSessions($userID);

        $data['expire'] = ($remember == true) ? strtotime($this->module->config('session')['remember']) : strtotime($this->module->config('session')['time']);

        $data['cookie'] = sha1($data['hash'].$this->module->config('session')['key']);

        $session = $this->app->db->insert($this->module->config('session')['table'], [
            'userID' => $userID,
            'hash' => $data['hash'],
            'expire' => date("Y-m-d H:i:s", $data['expire']),
            'ip' => self::getIP(),
            'agent' => $agent,
            'cookie' => $data['cookie']
        ]);

        if(!$session) {
            return false;
        }

        setcookie($this->module->config('session')['cookieName'], $data['hash'], $data['expire'], '/');
        $_COOKIE[$this->module->config('session')['cookieName']] = $data['hash'];

        return $data;
    }

    protected function deleteExistingSessions($userID) {
        return $this->app->db->delete($this->module->config('session')['table'], [
            "AND" => [
                'userID' => $userID
            ]
        ]);
    }

    protected function deleteSession($hash) {
        return $this->app->db->delete($this->module->config('session')['table'], [
            "AND" => [
                'hash' => $hash
            ]
        ]);
    }

    public function checkSession($hash) {
        if($this->attempt->isBlocked()) {
            $return['message'] = 'Login blocked';
            return false;
        }
        if(strlen($hash) != 40) {
            return false;
        }
        $session = $this->app->db->get($this->module->config('session')['table'], [
            'id',
            'userID',
            'expire',
            'ip',
            'cookie'
        ], [
            'hash' => $hash
        ]);
        if(!$session) {
            return false;
        }
        $sessionID = $session['id'];
        $userID = $session['userID'];
        $expire = strtotime($session['expire']);
        $current = strtotime(date("Y-m-d H:i:s"));
        if($current > $expire) {
            $this->deleteExistingSessions($userID);
            return false;
        }
        if(self::getIP() != $session['ip']) {
            return false;
        }
        if($session['cookie'] == sha1($hash.$this->module->config('session')['key'])) {
            return true;
        }
        return false;
    }

    public function getSessionID($hash) {
        $userID = $this->app->db->get($this->module->config('session')['table'], 'userID', [
            'hash' => $hash
        ]);
        if(!$userID) {
            return false;
        }
        return $userID;
    }

    public function isLogged() {
        if(!$this->isLogged) {
            $this->isLogged = $this->checkSession($this->getSessionHash());
        }
        return $this->isLogged;
    }

    protected static function getIP() {
        if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    protected function validateEmail($email) {
        $return['error'] = true;
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $return['message'] = 'Invalid email';
            return $return;
        }
        $return['error'] = false;
        return $return;
    }

    public function getSessionHash(){
        $cookieName = $this->module->config('session')['cookieName'];
        return isset($_COOKIE[$cookieName]) ? $_COOKIE[$cookieName] : false;
    }

    public function getCurrentUser() {
        if(is_null($this->userObj)) {
            $hash = $this->getSessionHash();
            if($hash === false) {
                return false;
            }
            $id = $this->getSessionID($hash);
            if($id === false) {
                return false;
            }
            $this->userObj = $this->app->user->getUser($id);
        }
        return $this->userObj;
    }

    public function getHash($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    public function comparePassword($id, $password) {
        $passwordDB = $this->app->db->get($this->module->config('table'), 'password', [
            'id' => $id
        ]);
        if(!$passwordDB) {
            return false;
        }
        return password_verify($password, $passwordDB);
    }

    public function passwordRehash($password, $hash, $id) {
        if(!password_verify($password, $hash)) {
            return false;
        }
        if(password_needs_rehash($hash, PASSWORD_DEFAULT, ['cost' => 10])) {
            $this->app->db->update($this->module->config('table'), [
                'password' => $this->getHash($password)
            ], [
                'id' => $id
            ]);
        }
        return true;
    }

}

?>
