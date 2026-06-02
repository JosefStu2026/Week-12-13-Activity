<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    public string $fromEmail  = 'system@engineershq.com';
    public string $fromName   = 'Core Architecture Lab';
    public string $recipients = '';

    // Switch protocol back to SMTP for network transmission
    public string $protocol   = 'smtp'; 
    public string $SMTPHost   = 'sandbox.smtp.mailtrap.io';
    public string $SMTPUser   = 'dba1723050351d'; // Replace with your Mailtrap user string
    public string $SMTPPass   = '57c95c077c0305'; // Replace with your Mailtrap password string
    public int $SMTPPort      = 2525;
    public string $SMTPCrypto = 'tls'; // Enforce safe Transport Layer Security 
    
    // Default system initialization values
    public string $mailType   = 'html'; // Prioritize rich layout markup [cite: 123, 147]
    public string $charset    = 'UTF-8';
    public bool $wordWrap     = true;
}

