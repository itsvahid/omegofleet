<?php

namespace App\Entity;

enum Role: string
{
    case ROLE_USER = 'ROLE_USER';
    case ROLE_COMPANY_ADMIN = 'ROLE_COMPANY_ADMIN';
    case ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
}
