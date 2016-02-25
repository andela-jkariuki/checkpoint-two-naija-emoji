<?php

namespace NaijaEmoji\Manager;

use Potato\Manager\PotatoModel;
use Carbon\Carbon;
use PDOException;

class UserManagerController extends PotatoModel
{
    protected static $table = "users";

    public static function createUser($request, $response)
    {
        $data = $request->getParsedBody();
        try {
            if (is_array($data) && count(array_diff(['username', 'password'], array_keys($data)))) {
                throw new PDOException("Missing some required fields");
            }

            $user = self::findRecord([
                'username' => $data['username'],
                'password' => hash("sha256", $data['password'])
            ]);

            if (is_array($user) && ! empty($user)) {

                $response = $response->withStatus(400);
                $message = json_encode([
                    'message' => "User already exists."
                ]);
            } else {
                // register user
                $user = new self();
                $user->username = $data['username'];
                $user->password = hash("sha256", $data['password']);

                if ($user->save()) {
                    $response = $response->withStatus(201);
                    $message = json_encode([
                        'message' => "User successfully registered."
                    ]);
                } else {
                    $response = $response->withStatus(400);
                    $message = json_encode([
                        'message' => "Error registering user."
                    ]);
                }
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(400);
            $message = json_encode([
                'message' => $e->getMessage()
            ]);
        }

        $response = $response->withHeader('Content-type', 'application/json');
        return $response->write($message);
    }

    public static function loginUser($request, $response)
    {
        $data = $request->getParsedBody();
        try {
            if (is_array($data) && count(array_diff(['username', 'password'], array_keys($data)))) {
                throw new PDOException("Missing some required fields");
            }

            $user = self::findRecord([
                'username' => $data['username'],
                'password' => hash("sha256", $data['password'])
            ]);

            if (is_array($user) && ! empty($user)) {
                $token = hash("sha256", $data['username'] . md5(3.142) . time() . rand(1, 1001));

                $tokenData = [
                    'token' => $token,
                    'expires' => time() + 86400,
                    'id' => $user[id]
                ];

                if (self::updateUserToken($tokenData)) {
                    $response = $response->withStatus(200);
                    $message = json_encode([
                        'message' => 'login successful',
                        'token' => $token
                    ]);
                } else {
                    $response = $response->withStatus(400);
                    $message = json_encode([
                        'message' => 'Error authenticating user.'
                    ]);
                }
            } else {
                $response = $response->withStatus(400);
                $message = json_encode([
                    'message' => "Invalid login credentials."
                ]);
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(400);
            $message = json_encode([
                'message' => $e->getMessage()
            ]);
        }

        $response = $response->withHeader('Content-type', 'application/json');
        return $response->write($message);
    }

    public static function logoutUser($request, $response)
    {
        try {
            if (is_array($request->getHeader("HTTP_TOKEN")) && count($request->getHeader("HTTP_TOKEN")) === 1) {
                $tokenInfo = self::findRecord([
                    'token' => $request->getHeader("HTTP_TOKEN")[0]
                ]);

                if (is_array($tokenInfo) && ! empty($tokenInfo)) {
                    $deleteToken = [
                        'id' => $tokenInfo['id'],
                        'token' => "",
                        'expires' => ""
                    ];

                    if (self::updateUserToken($deleteToken)) {
                        $response = $response->withStatus(200);
                        $message = json_encode([
                            'message' => "successfully logged out."
                        ]);
                    } else {
                        $response = $response->withStatus(400);
                        $message = json_encode([
                            'message' => "error logging out."
                        ]);
                    }
                } else {
                    $response = $response->withStatus(400);
                    $message = json_encode([
                        'message' => "Invalid token provided"
                    ]);
                }
            } else {
                $response = $response->withStatus(400);
                $message = json_encode([
                    'message' => "no token provided"
                ]);
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(400);
            $message = json_encode([
                'message' => $e->getMessage()
            ]);
        }

        $response = $response->withHeader('Content-type', 'application/json');
        return $response->write($message);
    }

    public static function updateUserToken(array $tokenData)
    {
        if (is_array($tokenData)) {
            $updateToken = self::find($tokenData['id']);

            $updateToken->token = $tokenData['token'];
            $updateToken->expires = $tokenData['expires'];
            if ($updateToken->save()) {
                return true;
            }
        }

        return false;
    }
}
