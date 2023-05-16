<?php
use RedBeanPHP\R;
use Firebase\JWT\JWT;

class AppMock {
}

$app = new AppMock();

class DataMock {
  public static function GetJwt($userId = 1) {
    Auth::CreateJwtSigningKey();

    $key = R::load('jwt', 1);

    $jwt = JWT::encode(array(
      'exp' => time() + (60 * 30), // 30 minutes
      'uid' => $userId,
      'mul' => 1
    ), $key->secret);

    $user = R::load('user', $userId);
    $user->active_token = $jwt;

    if ($userId == 3) {
      $user->security_level = SecurityLevel::UNPRIVILEGED;
    }

    R::store($user);

    return $jwt;
  }

  public static function CreateStandardUser() {
    $user = R::dispense('user');
    self::setUserDefaults($user);
    R::store($user);
  }

  public static function CreateBoardAdminUser() {
    $user = R::dispense('user');
    self::setUserDefaults($user);

    $user->username = 'boardadmin';
    $user->security_level = SecurityLevel::BOARD_ADMIN;
    R::store($user);
  }

  public static function CreateUnprivilegedUser() {
    $user = R::dispense('user');
    self::setUserDefaults($user);

    $user->username = 'badtester';
    $user->security_level = SecurityLevel::UNPRIVILEGED;
    R::store($user);
  }

  public static function CreateBoard() {
    $board = R::dispense('board');
    $board->name = 'test';
    $board->is_active = true;
  }

  private static function setUserDefaults(&$user) {
    $user->username = 'tester';
    $user->security_level = SecurityLevel::USER;
    $user->password_hash = 'hashpass1234';
    $user->email = 'user@example.com';
    $user->default_board_id = 0;
    $user->user_option_id = 0;
    $user->last_login = 123456789;
    $user->active_token = '';
  }
}

class LoggerMock implements Psr\Container\ContainerInterface {
  public function get($name) {
    if ($name === 'logger') {
      return new Logger();
    }

    return null;
  }

  public function has($id) { $id; }
}

class Logger {

  public function info() {
  }

  public function error() {
    // Uncomment to log errors to file
    // The tests cover errors, so there will be plenty to sift through
    // $msg = func_get_arg(0);
    // $err = 'API ERROR: ' . $msg . PHP_EOL;

    // $objs = func_get_args();
    // array_splice($objs, 0, 1);

    // ob_start();
    // foreach($objs as $obj) {
    //     var_dump($obj);
    // }
    // $strings = ob_get_clean();

    // file_put_contents('tests.log', [$err, $strings], FILE_APPEND);
  }

}

class RequestMock {
  public $invalidPayload = false;
  public $payload = null;
  public $hasHeader = true;
  public $header = null;
  public $throwInHeader = false;

  public function getBody() {
    if ($this->invalidPayload) {
      return '{}';
    }

    if ($this->payload) {
      return json_encode($this->payload);
    }

    return '';
  }

  public function hasHeader() {
    return $this->hasHeader;
  }

  public function getHeader($header) {
    if ($this->throwInHeader) {
      throw new Exception();
    }

    if ($this->header) {
      return $this->header;
    }

    return $header;
  }
}

class ResponseMock {
  public $status = 200;
  public $body;

  public function __construct() {
    $this->body = new RequestBodyMock();
  }

  public function withHeader($name, $value) {
    $name; $value;
    return $this;
  }

  public function withStatus($status) {
    $this->status = $status;

    return $this;
  }

  public function getStatusCode() {
    return $this->status;
  }

  public function getBody() {
    return $this->body;
  }

}

class RequestBodyMock {
  public $data;

  public function __construct() {
    $this->data = new ApiJson();
  }

  public function __toString() {
    return $this->data;
  }

  public function rewind() {}

    public function write($string) {
      $data = json_decode($string, true);

      foreach($data as $key => $value) {
        if ($key === 'alerts') {
          $this->data->alerts = [];

          foreach($data['alerts'] as $item) {
            $this->data->alerts[] = $item;
          }

          continue;
        }

        $this->data->{$key} = $value;
      }
    }

}

