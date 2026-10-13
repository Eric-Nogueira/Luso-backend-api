<?php

namespace Classes;

use RuntimeException;
use WilliamCosta\DotEnv\Environment;
use Kreait\Firebase\Factory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class User
{
    public function recoveryMatches($value, string $code, string $token): bool
    {
        if (!is_array($value)) {
            return false;
        }

        $currentCode = trim((string) ($value['code'] ?? ''));
        $currentToken = trim((string) ($value['token'] ?? ''));

        if ($currentCode !== '' || $currentToken !== '') {
            return $currentCode === $code && $currentToken === $token;
        }

        foreach ($value as $item) {
            if (self::recoveryMatches($item, $code, $token)) {
                return true;
            }
        }

        return false;
    }

    public function normalizeRecoveryValue(string $value): string
    {
        return trim(stripslashes($value), " \t\n\r\0\x0B\"'");
    }
    function newUser($data)
    {
        Environment::load(__DIR__);
        $factory = (new Factory())->withServiceAccount(getenv('GOOGLE_APPLICATION_CREDENTIALS'))->withDatabaseUri(getenv('DB_URL'));
        $database = $factory->createDatabase();

        $email = $data['email'] ?? null;
        $name = $data['name'] ?? null;
        $password = $data['password'] ?? null;

        // Validações básicas
        if (!$email || !$name || !$password) {
            return json_encode([
                'status' => 'error',
                'message' => 'Campos incompletos'
            ]);
        }

        // Verificar se o email já existe
        $users = $database->getReference('users')->getValue();
        if (is_array($users)) {
            foreach ($users as $user) {
                if (isset($user['email']) && $user['email'] === $email) {
                    return json_encode([
                        'status' => 'failed',
                        'message' => 'Usuário já cadastrado'
                    ]);
                }
            }
        }

        // Criar novo usuário
        $newUserRef = $database->getReference('users')->push();
        $newUserRef->set([
            'email' => $email,
            'username' => $name,
            'password' => $password // já está hasheado
        ]);

        // Retornar sucesso com os dados recebidos (para depuração)
        return json_encode([
            'status' => 'success',
            'received' => [
                'email' => $email,
                'name' => $name,
                // não retorne a senha por segurança, mas pode retornar um id
                'id' => $newUserRef->getKey()
            ]
        ]);
    }

    public function login(array $data)
    {
        Environment::load(__DIR__);
        $factory = (new Factory())->withServiceAccount(getenv('GOOGLE_APPLICATION_CREDENTIALS'))->withDatabaseUri(getenv('DB_URL'));
        $database = $factory->createDatabase();

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if ($email === null || $password === null) {
            $response = [
                'status' => 'error',
                'message' => 'Envie um JSON válido com email e password.'
            ];
        } else {
            $users = $database->getReference('users')->getValue();
            $checker = false;

            if (is_array($users)) {
                foreach ($users as $user) {
                    if (is_array($user) && (($user['email'] ?? null) === $email && $user['password'] === $password)) {
                        $checker = true;
                        break;
                    }
                }
            }

            if ($checker) {
                $response = [
                    'status' => 'success',
                    'received' => [
                        'email' => $email,
                        'id' => uniqid(),
                        'password' => $password
                    ],
                ];
            } else {
                $response = [
                    'message' => "Usuário não cadastrado"
                ];
            }
        }

        return json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function getQuestion($data)
    {
        Environment::load(__DIR__);
        $factory = (new Factory())
            ->withServiceAccount(getenv('GOOGLE_APPLICATION_CREDENTIALS'))
            ->withDatabaseUri(getenv('DB_URL'));
        $database = $factory->createDatabase();

        if ($data === null && !empty($_POST)) {
            $data = $_POST;
        }

        if ($data === null) {
            return json_encode([
                'status' => 'error',
                'message' => 'Corpo inválido ou ausente.'
            ], JSON_UNESCAPED_UNICODE);
        }

        $dificuldade = $data['fase'];
        // IDs que o app já usou nesta rodada (opcional).
        $exclude = $data['exclude'] ?? [];

        $reference = $database->getReference('questions');
        $snapshot = $reference
            ->orderByChild('nivel')
            ->equalTo($dificuldade)
            ->getSnapshot();

        $questoes = $snapshot->getValue();

        if (!is_array($questoes) || empty($questoes)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Nenhuma questão encontrada para esta fase.'
            ], JSON_UNESCAPED_UNICODE);
        }

        // Remove as questões já utilizadas.
        if (is_array($exclude) && !empty($exclude)) {
            foreach ($exclude as $id) {
                unset($questoes[$id]);
            }
        }

        if (empty($questoes)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Não há mais questões disponíveis nesta fase.'
            ], JSON_UNESCAPED_UNICODE);
        }

        $ids = array_keys($questoes);
        $idAleatorio = $ids[array_rand($ids)];
        $questaoSorteada = $questoes[$idAleatorio];

        $response = [
            'status' => 'success',
            'id' => $idAleatorio,   // <<< devolve o ID
            'question' => $questaoSorteada,
        ];

        return json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
