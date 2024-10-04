<?php

include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.user.php';

class UsersApiController extends ApiController {

    public function create($format) {
        $key = $this->requireApiKey();

        if (!$key) {
            return $this->exerr(401, __('API key no autorizada'));
        }

        $user = null;
        if (!strcasecmp($format, 'email')) {
            return $this->exerr(500, __('Email not supported at the moment'));
        } else {
            # Parse request body
            $data = $this->getRequest($format);
            
            // Validar los campos obligatorios
            if (empty($data['email']) || empty($data['full_name'])) {
                return $this->exerr(400, __('Email y nombre requeridos'));
            }
            
            // Validar que las contraseñas coincidan
            if (!empty($data['password']) && ($data['password'] !== $data['confirm_password'])) {
                return $this->exerr(400, __('Contrasenas no coincided'));
            }

            // Verificar si ya existe un usuario con el mismo email
            $existing_user = User::lookupByEmail($data['email']);
            if ($existing_user) {
                return $this->exerr(409, __('Ya existe un usuario con este email')); // 409 Conflict
            }

            $user = $this->createUser($data);
        }

        if ($user) {
            return "Usuario creado: "."\nID: ".$user->getId()."\nEmail: ".$user->getEmail();
        } else {
            return $this->exerr(500, __('No fue posible crear el usuario.'));
        }
    }

    private function createUser($data) {
        $user_data = array(
            'email' => $data['email'],
            'name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'passwd' => $data['password'] ?? null,
        );

        $user = User::fromVars($user_data);

        $errors = [];
        if ($acct = UserAccount::register($user, array(
            'sendemail' => false,
            'passwd1' => $data['password'],
            'timezone' => $data['timezone']
        ), $errors)) {
        // Usuario registrado correctamente
            return $user; // Usuario creado exitosamente
        } else {
        // Manejar errores de registro
            return null;
        }
    }
}
