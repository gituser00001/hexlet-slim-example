<?php

namespace App;

class Validator implements ValidatorInterface
{
    public function validate(array $user): array
    {
        // BEGIN (write your solution here)
        $errors = [];
        if (empty($user['name'])) {
            $errors['name'] = "Can't be blank";
        } elseif (strlen($user['name']) < 4) {
            $errors['name'] = "Nickname must be grater than 4 characters";
        }

        return $errors;
        // END
    }
}
