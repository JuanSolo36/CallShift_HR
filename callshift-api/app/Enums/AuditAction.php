<?php

namespace App\Enums;

enum AuditAction: string
{
    case LOGIN    = 'LOGIN';
    case LOGOUT   = 'LOGOUT';
    case CREATE   = 'CREATE';
    case UPDATE   = 'UPDATE';
    case DELETE   = 'DELETE';
    case GENERATE = 'GENERATE';
    case PUBLISH  = 'PUBLISH';
    case MODIFY   = 'MODIFY';
    case EXPORT   = 'EXPORT';
    case RESTORE  = 'RESTORE';

    public function label(): string
    {
        return match ($this) {
            self::LOGIN    => 'Inicio de Sesión',
            self::LOGOUT   => 'Cierre de Sesión',
            self::CREATE   => 'Creación de Registro',
            self::UPDATE   => 'Actualización de Datos',
            self::DELETE   => 'Eliminación / Baja Lógica',
            self::GENERATE => 'Generación de Horarios',
            self::PUBLISH  => 'Publicación de Versión',
            self::MODIFY   => 'Modificación de Turno Publicado',
            self::EXPORT   => 'Exportación de Datos / Reporte',
            self::RESTORE  => 'Restauración de Versión Histórica',
        };
    }
}
