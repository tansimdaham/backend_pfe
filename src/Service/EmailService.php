<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Twig\Environment;

class EmailService
{
    private MailerInterface $mailer;
    private RouterInterface $router;
    private string $senderEmail;
    private Environment $twig;

    public function __construct(
        MailerInterface $mailer,
        RouterInterface $router,
        Environment $twig,
        string $senderEmail = 'tansimdaham@gmail.com'
    ) {
        $this->mailer = $mailer;
        $this->router = $router;
        $this->twig = $twig;
        $this->senderEmail = $senderEmail;
    }

    public function sendApprovalEmail(Utilisateur $user): bool
    {
        try {
            // Logs détaillés pour le suivi de l'envoi d'email
            $logPrefix = '[EMAIL-SERVICE] [APPROVAL]';
            error_log($logPrefix . ' Début de l\'envoi de l\'email d\'approbation à ' . $user->getEmail());
            error_log($logPrefix . ' Préparation de l\'email d\'approbation pour ' . $user->getEmail());

            $loginUrl = 'http://localhost:5173/login';

            // Contenu HTML de l'email avec design professionnel
            $htmlContent = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Votre compte a été approuvé</title>
                <style>
                    @import url(\'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap\');
                    @import url(\'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap\');

                    body {
                        font-family: \'Poppins\', Arial, sans-serif;
                        line-height: 1.6;
                        color: #4a5568;
                        margin: 0;
                        padding: 0;
                        background-color: #f0f4f8;
                        background-image: url(\'data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z" fill="%239C92AC" fill-opacity="0.05" fill-rule="evenodd"/%3E%3C/svg%3E\');
                    }

                    .email-container {
                        max-width: 680px;
                        margin: 40px auto;
                        background-color: #ffffff;
                        border-radius: 16px;
                        overflow: hidden;
                        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
                        border: 1px solid rgba(226, 232, 240, 0.8);
                    }

                    .email-header {
                        background: linear-gradient(135deg, #5e72e4 0%, #825ee4 100%);
                        padding: 40px;
                        text-align: center;
                        position: relative;
                        overflow: hidden;
                    }

                    .email-header::before {
                        content: "";
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background-image: url(\'data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z" fill="%23ffffff" fill-opacity="0.05" fill-rule="evenodd"/%3E%3C/svg%3E\');
                        opacity: 0.8;
                    }

                    .logo {
                        margin-bottom: 25px;
                        position: relative;
                        z-index: 1;
                        transform: scale(1.1);
                        filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
                    }

                    .logo svg {
                        filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.2));
                    }

                    .email-header h1 {
                        color: #ffffff;
                        margin: 0;
                        font-family: \'Playfair Display\', serif;
                        font-size: 32px;
                        font-weight: 700;
                        letter-spacing: 0.5px;
                        position: relative;
                        z-index: 1;
                        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    }

                    .email-body {
                        padding: 50px;
                        color: #4a5568;
                        background-color: #ffffff;
                        background-image: url(\'data:image/svg+xml,%3Csvg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="%239C92AC" fill-opacity="0.03" fill-rule="evenodd"%3E%3Ccircle cx="3" cy="3" r="3"/%3E%3Ccircle cx="13" cy="13" r="3"/%3E%3C/g%3E%3C/svg%3E\');
                    }

                    .greeting {
                        font-family: \'Playfair Display\', serif;
                        font-size: 26px;
                        font-weight: 600;
                        color: #2d3748;
                        margin-bottom: 25px;
                        position: relative;
                        display: inline-block;
                    }

                    .greeting::after {
                        content: "";
                        position: absolute;
                        bottom: -8px;
                        left: 0;
                        width: 40%;
                        height: 3px;
                        background: linear-gradient(90deg, #5e72e4, transparent);
                        border-radius: 3px;
                    }

                    .message {
                        font-size: 16px;
                        line-height: 1.8;
                        margin-bottom: 35px;
                        color: #4a5568;
                    }

                    .message p {
                        margin: 0 0 22px;
                    }

                    .cta-container {
                        text-align: center;
                        margin: 40px 0;
                    }

                    .cta-button {
                        display: inline-block;
                        padding: 16px 35px;
                        background: linear-gradient(135deg, #5e72e4 0%, #825ee4 100%);
                        color: #ffffff !important;
                        text-decoration: none;
                        font-weight: 600;
                        font-size: 16px;
                        border-radius: 50px;
                        box-shadow: 0 8px 15px rgba(94, 114, 228, 0.3);
                        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                        position: relative;
                        overflow: hidden;
                    }

                    .cta-button::before {
                        content: "";
                        position: absolute;
                        top: 0;
                        left: -100%;
                        width: 100%;
                        height: 100%;
                        background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
                        transition: all 0.6s ease;
                    }

                    .cta-button:hover {
                        transform: translateY(-3px) scale(1.02);
                        box-shadow: 0 12px 20px rgba(94, 114, 228, 0.4);
                    }

                    .cta-button:hover::before {
                        left: 100%;
                    }

                    .info-box {
                        background-color: #f8faff;
                        border-left: 4px solid #5e72e4;
                        padding: 25px;
                        margin: 35px 0;
                        border-radius: 0 12px 12px 0;
                        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
                        position: relative;
                        overflow: hidden;
                    }

                    .info-box::before {
                        content: "";
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background-image: url(\'data:image/svg+xml,%3Csvg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M0 0h20v20H0V0zm10 17a7 7 0 1 0 0-14 7 7 0 0 0 0 14z" fill="%235e72e4" fill-opacity="0.03" fill-rule="evenodd"/%3E%3C/svg%3E\');
                    }

                    .info-box h3 {
                        margin-top: 0;
                        color: #5e72e4;
                        font-size: 20px;
                        font-family: \'Playfair Display\', serif;
                        position: relative;
                    }

                    .info-box p {
                        margin-bottom: 0;
                        position: relative;
                    }

                    .info-box ul {
                        padding-left: 20px;
                        position: relative;
                    }

                    .info-box li {
                        margin-bottom: 8px;
                        position: relative;
                    }

                    .signature {
                        margin-top: 40px;
                        padding-top: 25px;
                        border-top: 1px solid #e2e8f0;
                    }

                    .signature p {
                        margin: 6px 0;
                    }

                    .team-name {
                        font-weight: 600;
                        color: #5e72e4;
                        font-family: \'Playfair Display\', serif;
                        font-size: 18px;
                    }

                    .email-footer {
                        background-color: #f8faff;
                        padding: 30px 50px;
                        text-align: center;
                        color: #718096;
                        font-size: 14px;
                        border-top: 1px solid #e2e8f0;
                    }

                    .social-links {
                        margin: 25px 0;
                    }

                    .social-links a {
                        display: inline-block;
                        margin: 0 12px;
                        color: #5e72e4;
                        text-decoration: none;
                        transition: all 0.3s ease;
                        position: relative;
                    }

                    .social-links a::after {
                        content: "";
                        position: absolute;
                        bottom: -5px;
                        left: 0;
                        width: 0;
                        height: 1px;
                        background-color: #5e72e4;
                        transition: width 0.3s ease;
                    }

                    .social-links a:hover::after {
                        width: 100%;
                    }

                    .footer-links {
                        margin: 20px 0;
                    }

                    .footer-links a {
                        color: #5e72e4;
                        text-decoration: none;
                        margin: 0 12px;
                        transition: all 0.3s ease;
                        position: relative;
                    }

                    .footer-links a::after {
                        content: "";
                        position: absolute;
                        bottom: -5px;
                        left: 0;
                        width: 0;
                        height: 1px;
                        background-color: #5e72e4;
                        transition: width 0.3s ease;
                    }

                    .footer-links a:hover::after {
                        width: 100%;
                    }

                    .copyright {
                        margin-top: 20px;
                        font-size: 13px;
                        color: #a0aec0;
                    }

                    @media screen and (max-width: 600px) {
                        .email-container {
                            margin: 20px auto;
                        }

                        .email-header, .email-body, .email-footer {
                            padding: 30px;
                        }

                        .email-header h1 {
                            font-size: 26px;
                        }

                        .greeting {
                            font-size: 22px;
                        }

                        .message {
                            font-size: 15px;
                        }

                        .cta-button {
                            padding: 14px 28px;
                            font-size: 15px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="email-container">
                    <div class="email-header">
                        <div class="logo">
                            <!-- Logo PharmaLearn -->
                            <svg width="180" height="40" viewBox="0 0 180 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 0C8.954 0 0 8.954 0 20C0 31.046 8.954 40 20 40C31.046 40 40 31.046 40 20C40 8.954 31.046 0 20 0ZM20 6C26.627 6 32 11.373 32 18C32 24.627 26.627 30 20 30C13.373 30 8 24.627 8 18C8 11.373 13.373 6 20 6Z" fill="white"/>
                                <path d="M50 10H58.5C63.5 10 67 13.5 67 18.5C67 23.5 63.5 27 58.5 27H50V10ZM58 23C61 23 63 21 63 18.5C63 16 61 14 58 14H54V23H58Z" fill="white"/>
                                <path d="M70 10H74V23H84V27H70V10Z" fill="white"/>
                                <path d="M87 10H101V14H91V16.5H99V20.5H91V23H101V27H87V10Z" fill="white"/>
                                <path d="M104 10H108V19C108 21.5 109.5 23 112 23C114.5 23 116 21.5 116 19V10H120V19C120 23.5 117 27 112 27C107 27 104 23.5 104 19V10Z" fill="white"/>
                                <path d="M123 10H127V27H123V10Z" fill="white"/>
                                <path d="M131 10H135V23H145V27H131V10Z" fill="white"/>
                                <path d="M147 10H151V16.5H159V10H163V27H159V20.5H151V27H147V10Z" fill="white"/>
                            </svg>
                        </div>
                        <h1>Votre compte a été approuvé</h1>
                    </div>

                    <div class="email-body">
                        <div class="greeting">Bonjour ' . $user->getName() . ',</div>

                        <div class="message">
                            <p>Nous sommes ravis de vous informer que <strong>votre compte a été approuvé</strong> par notre équipe administrative.</p>

                            <p>Vous pouvez maintenant vous connecter à votre compte et accéder à toutes les fonctionnalités de la plateforme PharmaLearn, y compris les cours, les quiz et les ressources pédagogiques.</p>
                        </div>

                        <div class="cta-container">
                            <a href="' . $loginUrl . '" class="cta-button">Se connecter à mon compte</a>
                        </div>

                        <div class="info-box">
                            <h3>Prochaines étapes</h3>
                            <p>Une fois connecté, nous vous recommandons de :</p>
                            <ul>
                                <li>Compléter votre profil</li>
                                <li>Explorer les cours disponibles</li>
                                <li>Rejoindre la communauté d\'apprenants</li>
                            </ul>
                        </div>

                        <div class="signature">
                            <p>Si vous avez des questions ou besoin d\'assistance, n\'hésitez pas à contacter notre équipe de support.</p>
                            <p>Cordialement,</p>
                            <p class="team-name">L\'équipe PharmaLearn</p>
                        </div>
                    </div>

                    <div class="email-footer">
                        <div class="social-links">
                            <a href="#">Facebook</a>
                            <a href="#">Twitter</a>
                            <a href="#">LinkedIn</a>
                            <a href="#">Instagram</a>
                        </div>

                        <div class="footer-links">
                            <a href="#">Centre d\'aide</a>
                            <a href="#">Conditions d\'utilisation</a>
                            <a href="#">Politique de confidentialité</a>
                        </div>

                        <div class="copyright">
                            <p>© ' . date('Y') . ' PharmaLearn. Tous droits réservés.</p>
                            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            ';

            // Version texte pour les clients qui ne supportent pas le HTML
            $textContent = 'Bonjour ' . $user->getName() . ',

Votre compte a été approuvé. Vous pouvez vous connecter à ' . $loginUrl . '

Cordialement,
L\'équipe PharmaLearn';

            $email = (new Email())
                ->from(new Address($this->senderEmail, 'PharmaLearn'))
                ->to($user->getEmail())
                ->subject('Votre compte a été approuvé')
                ->text($textContent)
                ->html($htmlContent);

            error_log($logPrefix . ' Email configuré, tentative d\'envoi...');

            $this->mailer->send($email);

            error_log($logPrefix . ' ✅ Email d\'approbation envoyé avec succès à ' . $user->getEmail());

            return true;
        } catch (\Exception $e) {
            $errorMsg = $logPrefix . ' ERREUR lors de l\'envoi de l\'email d\'approbation: ' . $e->getMessage();
            error_log($errorMsg);
            error_log($logPrefix . ' Trace: ' . $e->getTraceAsString());
            error_log($logPrefix . ' ❌ ÉCHEC de l\'envoi de l\'email d\'approbation à ' . $user->getEmail());
            error_log($logPrefix . ' ❌ Erreur: ' . $e->getMessage());

            // Si la configuration est null://null, afficher un message spécifique
            if (strpos($e->getMessage(), 'null://null') !== false) {
                error_log($logPrefix . ' ℹ️ La configuration MAILER_DSN est définie sur \'null://null\'. ' .
                     'Les emails ne sont pas réellement envoyés. Modifiez le fichier .env pour configurer un transport d\'email réel.');
            }

            // Relancer l'exception pour qu'elle soit gérée par l'appelant
            throw $e;
        }
    }

    public function sendRejectionEmail(Utilisateur $user, ?string $reason = null): bool
    {
        try {
            // Logs détaillés pour le suivi de l'envoi d'email
            $logPrefix = '[EMAIL-SERVICE] [REJECTION]';
            error_log($logPrefix . ' Début de l\'envoi de l\'email de rejet à ' . $user->getEmail());
            error_log($logPrefix . ' Préparation de l\'email de rejet pour ' . $user->getEmail());

            $registerUrl = 'http://localhost:5173/register';

            // Version texte pour les clients qui ne supportent pas le HTML
            $textContent = 'Votre demande d\'inscription a été rejetée.';
            if ($reason) {
                $textContent .= ' Raison: ' . $reason;
            }
            $textContent .= ' Vous pouvez vous inscrire à nouveau à ' . $registerUrl;

            // Contenu HTML de l'email avec design professionnel
            $htmlContent = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Votre demande d\'inscription a été rejetée</title>
                <style>
                    @import url(\'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap\');
                    @import url(\'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap\');

                    body {
                        font-family: \'Poppins\', Arial, sans-serif;
                        line-height: 1.6;
                        color: #4a5568;
                        margin: 0;
                        padding: 0;
                        background-color: #f0f4f8;
                        background-image: url(\'data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z" fill="%239C92AC" fill-opacity="0.05" fill-rule="evenodd"/%3E%3C/svg%3E\');
                    }

                    .email-container {
                        max-width: 680px;
                        margin: 40px auto;
                        background-color: #ffffff;
                        border-radius: 16px;
                        overflow: hidden;
                        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
                        border: 1px solid rgba(226, 232, 240, 0.8);
                    }

                    .email-header {
                        background: linear-gradient(135deg, #e05252 0%, #d0546a 100%);
                        padding: 40px;
                        text-align: center;
                        position: relative;
                        overflow: hidden;
                    }

                    .email-header::before {
                        content: "";
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background-image: url(\'data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z" fill="%23ffffff" fill-opacity="0.05" fill-rule="evenodd"/%3E%3C/svg%3E\');
                        opacity: 0.8;
                    }

                    .logo {
                        margin-bottom: 25px;
                        position: relative;
                        z-index: 1;
                        transform: scale(1.1);
                        filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
                    }

                    .logo svg {
                        filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.2));
                    }

                    .email-header h1 {
                        color: #ffffff;
                        margin: 0;
                        font-family: \'Playfair Display\', serif;
                        font-size: 32px;
                        font-weight: 700;
                        letter-spacing: 0.5px;
                        position: relative;
                        z-index: 1;
                        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    }

                    .email-body {
                        padding: 50px;
                        color: #4a5568;
                        background-color: #ffffff;
                        background-image: url(\'data:image/svg+xml,%3Csvg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="%239C92AC" fill-opacity="0.03" fill-rule="evenodd"%3E%3Ccircle cx="3" cy="3" r="3"/%3E%3Ccircle cx="13" cy="13" r="3"/%3E%3C/g%3E%3C/svg%3E\');
                    }

                    .greeting {
                        font-family: \'Playfair Display\', serif;
                        font-size: 26px;
                        font-weight: 600;
                        color: #2d3748;
                        margin-bottom: 25px;
                        position: relative;
                        display: inline-block;
                    }

                    .greeting::after {
                        content: "";
                        position: absolute;
                        bottom: -8px;
                        left: 0;
                        width: 40%;
                        height: 3px;
                        background: linear-gradient(90deg, #e05252, transparent);
                        border-radius: 3px;
                    }

                    .message {
                        font-size: 16px;
                        line-height: 1.8;
                        margin-bottom: 35px;
                        color: #4a5568;
                    }

                    .message p {
                        margin: 0 0 22px;
                    }

                    .cta-container {
                        text-align: center;
                        margin: 40px 0;
                    }

                    .cta-button {
                        display: inline-block;
                        padding: 16px 35px;
                        background: linear-gradient(135deg, #5e72e4 0%, #825ee4 100%);
                        color: #ffffff !important;
                        text-decoration: none;
                        font-weight: 600;
                        font-size: 16px;
                        border-radius: 50px;
                        box-shadow: 0 8px 15px rgba(94, 114, 228, 0.3);
                        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                        position: relative;
                        overflow: hidden;
                    }

                    .cta-button::before {
                        content: "";
                        position: absolute;
                        top: 0;
                        left: -100%;
                        width: 100%;
                        height: 100%;
                        background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
                        transition: all 0.6s ease;
                    }

                    .cta-button:hover {
                        transform: translateY(-3px) scale(1.02);
                        box-shadow: 0 12px 20px rgba(94, 114, 228, 0.4);
                    }

                    .cta-button:hover::before {
                        left: 100%;
                    }

                    .reason-box {
                        background-color: #fff8f8;
                        border-left: 4px solid #e05252;
                        padding: 25px;
                        margin: 35px 0;
                        border-radius: 0 12px 12px 0;
                        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
                        position: relative;
                        overflow: hidden;
                    }

                    .reason-box::before {
                        content: "";
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background-image: url(\'data:image/svg+xml,%3Csvg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M0 0h20v20H0V0zm10 17a7 7 0 1 0 0-14 7 7 0 0 0 0 14z" fill="%23e05252" fill-opacity="0.03" fill-rule="evenodd"/%3E%3C/svg%3E\');
                    }

                    .reason-box h3 {
                        margin-top: 0;
                        color: #e05252;
                        font-size: 20px;
                        font-family: \'Playfair Display\', serif;
                        position: relative;
                    }

                    .reason-box p {
                        margin-bottom: 0;
                        position: relative;
                    }

                    .info-box {
                        background-color: #f8faff;
                        border-left: 4px solid #5e72e4;
                        padding: 25px;
                        margin: 35px 0;
                        border-radius: 0 12px 12px 0;
                        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
                        position: relative;
                        overflow: hidden;
                    }

                    .info-box::before {
                        content: "";
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background-image: url(\'data:image/svg+xml,%3Csvg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M0 0h20v20H0V0zm10 17a7 7 0 1 0 0-14 7 7 0 0 0 0 14z" fill="%235e72e4" fill-opacity="0.03" fill-rule="evenodd"/%3E%3C/svg%3E\');
                    }

                    .info-box h3 {
                        margin-top: 0;
                        color: #5e72e4;
                        font-size: 20px;
                        font-family: \'Playfair Display\', serif;
                        position: relative;
                    }

                    .info-box p {
                        margin-bottom: 0;
                        position: relative;
                    }

                    .info-box ul {
                        padding-left: 20px;
                        position: relative;
                    }

                    .info-box li {
                        margin-bottom: 8px;
                        position: relative;
                    }

                    .signature {
                        margin-top: 40px;
                        padding-top: 25px;
                        border-top: 1px solid #e2e8f0;
                    }

                    .signature p {
                        margin: 6px 0;
                    }

                    .team-name {
                        font-weight: 600;
                        color: #5e72e4;
                        font-family: \'Playfair Display\', serif;
                        font-size: 18px;
                    }

                    .email-footer {
                        background-color: #f8faff;
                        padding: 30px 50px;
                        text-align: center;
                        color: #718096;
                        font-size: 14px;
                        border-top: 1px solid #e2e8f0;
                    }

                    .social-links {
                        margin: 25px 0;
                    }

                    .social-links a {
                        display: inline-block;
                        margin: 0 12px;
                        color: #5e72e4;
                        text-decoration: none;
                        transition: all 0.3s ease;
                        position: relative;
                    }

                    .social-links a::after {
                        content: "";
                        position: absolute;
                        bottom: -5px;
                        left: 0;
                        width: 0;
                        height: 1px;
                        background-color: #5e72e4;
                        transition: width 0.3s ease;
                    }

                    .social-links a:hover::after {
                        width: 100%;
                    }

                    .footer-links {
                        margin: 20px 0;
                    }

                    .footer-links a {
                        color: #5e72e4;
                        text-decoration: none;
                        margin: 0 12px;
                        transition: all 0.3s ease;
                        position: relative;
                    }

                    .footer-links a::after {
                        content: "";
                        position: absolute;
                        bottom: -5px;
                        left: 0;
                        width: 0;
                        height: 1px;
                        background-color: #5e72e4;
                        transition: width 0.3s ease;
                    }

                    .footer-links a:hover::after {
                        width: 100%;
                    }

                    .copyright {
                        margin-top: 20px;
                        font-size: 13px;
                        color: #a0aec0;
                    }

                    @media screen and (max-width: 600px) {
                        .email-container {
                            margin: 20px auto;
                        }

                        .email-header, .email-body, .email-footer {
                            padding: 30px;
                        }

                        .email-header h1 {
                            font-size: 26px;
                        }

                        .greeting {
                            font-size: 22px;
                        }

                        .message {
                            font-size: 15px;
                        }

                        .cta-button {
                            padding: 14px 28px;
                            font-size: 15px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="email-container">
                    <div class="email-header">
                        <div class="logo">
                            <!-- Logo PharmaLearn -->
                            <svg width="180" height="40" viewBox="0 0 180 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 0C8.954 0 0 8.954 0 20C0 31.046 8.954 40 20 40C31.046 40 40 31.046 40 20C40 8.954 31.046 0 20 0ZM20 6C26.627 6 32 11.373 32 18C32 24.627 26.627 30 20 30C13.373 30 8 24.627 8 18C8 11.373 13.373 6 20 6Z" fill="white"/>
                                <path d="M50 10H58.5C63.5 10 67 13.5 67 18.5C67 23.5 63.5 27 58.5 27H50V10ZM58 23C61 23 63 21 63 18.5C63 16 61 14 58 14H54V23H58Z" fill="white"/>
                                <path d="M70 10H74V23H84V27H70V10Z" fill="white"/>
                                <path d="M87 10H101V14H91V16.5H99V20.5H91V23H101V27H87V10Z" fill="white"/>
                                <path d="M104 10H108V19C108 21.5 109.5 23 112 23C114.5 23 116 21.5 116 19V10H120V19C120 23.5 117 27 112 27C107 27 104 23.5 104 19V10Z" fill="white"/>
                                <path d="M123 10H127V27H123V10Z" fill="white"/>
                                <path d="M131 10H135V23H145V27H131V10Z" fill="white"/>
                                <path d="M147 10H151V16.5H159V10H163V27H159V20.5H151V27H147V10Z" fill="white"/>
                            </svg>
                        </div>
                        <h1>Demande d\'Inscription Rejetée</h1>
                    </div>

                    <div class="email-body">
                        <div class="greeting">Bonjour ' . $user->getName() . ',</div>

                        <div class="message">
                            <p>Nous sommes désolés de vous informer que votre demande d\'inscription à la plateforme PharmaLearn a été rejetée.</p>
                        </div>';

            if ($reason) {
                $htmlContent .= '
                        <div class="reason-box">
                            <h3>Raison du rejet</h3>
                            <p>' . $reason . '</p>
                        </div>';
            }

            $htmlContent .= '
                        <div class="info-box">
                            <h3>Que faire maintenant ?</h3>
                            <p>Vous pouvez soumettre une nouvelle demande d\'inscription en corrigeant les informations nécessaires. Si vous avez des questions, n\'hésitez pas à contacter notre équipe de support.</p>
                        </div>

                        <div class="cta-container">
                            <a href="' . $registerUrl . '" class="cta-button">S\'inscrire à nouveau</a>
                        </div>

                        <div class="signature">
                            <p>Cordialement,</p>
                            <p class="team-name">L\'équipe PharmaLearn</p>
                        </div>
                    </div>

                    <div class="email-footer">
                        <div class="social-links">
                            <a href="#">Facebook</a>
                            <a href="#">Twitter</a>
                            <a href="#">LinkedIn</a>
                            <a href="#">Instagram</a>
                        </div>

                        <div class="footer-links">
                            <a href="#">Centre d\'aide</a>
                            <a href="#">Conditions d\'utilisation</a>
                            <a href="#">Politique de confidentialité</a>
                        </div>

                        <div class="copyright">
                            <p>© ' . date('Y') . ' PharmaLearn. Tous droits réservés.</p>
                            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            ';

            $email = (new Email())
                ->from(new Address($this->senderEmail, 'PharmaLearn'))
                ->to($user->getEmail())
                ->subject('Votre demande d\'inscription a été rejetée')
                ->text($textContent)
                ->html($htmlContent);

            error_log($logPrefix . ' Email configuré, tentative d\'envoi...');

            $this->mailer->send($email);

            error_log($logPrefix . ' ✅ Email de rejet envoyé avec succès à ' . $user->getEmail());

            return true;
        } catch (\Exception $e) {
            $errorMsg = $logPrefix . ' ERREUR lors de l\'envoi de l\'email de rejet: ' . $e->getMessage();
            error_log($errorMsg);
            error_log($logPrefix . ' Trace: ' . $e->getTraceAsString());
            error_log($logPrefix . ' ❌ ÉCHEC de l\'envoi de l\'email de rejet à ' . $user->getEmail());
            error_log($logPrefix . ' ❌ Erreur: ' . $e->getMessage());

            // Si la configuration est null://null, afficher un message spécifique
            if (strpos($e->getMessage(), 'null://null') !== false) {
                error_log($logPrefix . ' ℹ️ La configuration MAILER_DSN est définie sur \'null://null\'. ' .
                     'Les emails ne sont pas réellement envoyés. Modifiez le fichier .env pour configurer un transport d\'email réel.');
            }

            // Relancer l'exception pour qu'elle soit gérée par l'appelant
            throw $e;
        }
    }

    /**
     * Envoie un email de notification pour informer l'utilisateur qu'il a obtenu un certificat
     *
     * @param string $email L'adresse email du destinataire
     * @param string $name Le nom du destinataire
     * @param string $coursTitre Le titre du cours pour lequel le certificat a été obtenu
     * @param int $certificatId L'ID du certificat généré
     * @return bool True si l'email a été envoyé avec succès
     * @throws \Exception Si une erreur survient lors de l'envoi de l'email
     */
    public function sendCertificateNotificationEmail(string $email, string $name, string $coursTitre, int $certificatId): bool
    {
        try {
            // Logs détaillés pour le suivi de l'envoi d'email
            $logPrefix = '[EMAIL-SERVICE] [CERTIFICATE]';
            error_log($logPrefix . ' Début de l\'envoi de l\'email de notification de certificat à ' . $email);
            error_log($logPrefix . ' Préparation de l\'email de notification pour ' . $email);

            // URL pour voir le certificat (à adapter selon votre structure de routes frontend)
            $certificateUrl = 'http://localhost:5173/certificat/' . $certificatId;

            // Version texte pour les clients qui ne supportent pas le HTML
            $textContent = 'Félicitations ' . $name . ' !';
            $textContent .= "\n\nVous avez obtenu un certificat pour le cours \"" . $coursTitre . "\".";
            $textContent .= "\n\nVous avez complété avec succès tous les quiz du cours. Ce certificat atteste de vos compétences acquises.";
            $textContent .= "\n\nVous pouvez consulter et télécharger votre certificat à l'adresse suivante : " . $certificateUrl;
            $textContent .= "\n\nMerci pour votre engagement et votre réussite !";
            $textContent .= "\n\nL'équipe PharmaLearn";

            // Contenu HTML de l'email avec design professionnel
            $htmlContent = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Félicitations ! Vous avez obtenu un certificat</title>
                <style>
                    @import url(\'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap\');
                    @import url(\'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap\');

                    body {
                        font-family: \'Poppins\', Arial, sans-serif;
                        line-height: 1.6;
                        color: #4a5568;
                        margin: 0;
                        padding: 0;
                        background-color: #f0f4f8;
                        background-image: url(\'data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z" fill="%239C92AC" fill-opacity="0.05" fill-rule="evenodd"/%3E%3C/svg%3E\');
                    }

                    .email-container {
                        max-width: 680px;
                        margin: 40px auto;
                        background-color: #ffffff;
                        border-radius: 16px;
                        overflow: hidden;
                        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
                        border: 1px solid rgba(226, 232, 240, 0.8);
                    }

                    .email-header {
                        background: linear-gradient(135deg, #36b37e 0%, #1aae6f 100%);
                        padding: 40px;
                        text-align: center;
                        position: relative;
                        overflow: hidden;
                    }

                    .email-header::before {
                        content: "";
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background-image: url(\'data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z" fill="%23ffffff" fill-opacity="0.05" fill-rule="evenodd"/%3E%3C/svg%3E\');
                        opacity: 0.8;
                    }

                    .logo {
                        margin-bottom: 25px;
                        position: relative;
                        z-index: 1;
                        transform: scale(1.1);
                        filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
                    }

                    .logo svg {
                        filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.2));
                    }

                    .email-header h1 {
                        color: #ffffff;
                        margin: 0;
                        font-family: \'Playfair Display\', serif;
                        font-size: 32px;
                        font-weight: 700;
                        letter-spacing: 0.5px;
                        position: relative;
                        z-index: 1;
                        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    }

                    .email-body {
                        padding: 50px;
                        color: #4a5568;
                        background-color: #ffffff;
                        background-image: url(\'data:image/svg+xml,%3Csvg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="%239C92AC" fill-opacity="0.03" fill-rule="evenodd"%3E%3Ccircle cx="3" cy="3" r="3"/%3E%3Ccircle cx="13" cy="13" r="3"/%3E%3C/g%3E%3C/svg%3E\');
                    }

                    .greeting {
                        font-family: \'Playfair Display\', serif;
                        font-size: 28px;
                        font-weight: 700;
                        color: #36b37e;
                        margin-bottom: 25px;
                        text-align: center;
                        position: relative;
                        display: inline-block;
                        width: 100%;
                    }

                    .greeting::after {
                        content: "";
                        position: absolute;
                        bottom: -8px;
                        left: 50%;
                        transform: translateX(-50%);
                        width: 120px;
                        height: 3px;
                        background: linear-gradient(90deg, transparent, #36b37e, transparent);
                        border-radius: 3px;
                    }

                    .message {
                        font-size: 16px;
                        line-height: 1.8;
                        margin-bottom: 35px;
                        color: #4a5568;
                    }

                    .message p {
                        margin: 0 0 22px;
                    }

                    .certificate-box {
                        background-color: #f0fff4;
                        border: 2px solid #36b37e;
                        padding: 30px;
                        margin: 35px 0;
                        border-radius: 16px;
                        text-align: center;
                        position: relative;
                        box-shadow: 0 10px 25px rgba(54, 179, 126, 0.1);
                        overflow: hidden;
                    }

                    .certificate-box::before {
                        content: "";
                        position: absolute;
                        top: -10px;
                        left: 50%;
                        transform: translateX(-50%);
                        width: 100px;
                        height: 20px;
                        background-color: #ffffff;
                        border-radius: 10px;
                        z-index: 1;
                    }

                    .certificate-box::after {
                        content: "🏆";
                        position: absolute;
                        top: -18px;
                        left: 50%;
                        transform: translateX(-50%);
                        font-size: 28px;
                        z-index: 2;
                        filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.1));
                    }

                    .certificate-title {
                        font-size: 22px;
                        font-weight: 600;
                        font-family: \'Playfair Display\', serif;
                        color: #36b37e;
                        margin: 15px 0;
                        position: relative;
                        display: inline-block;
                    }

                    .certificate-title::before,
                    .certificate-title::after {
                        content: "✦";
                        color: #36b37e;
                        opacity: 0.5;
                        font-size: 16px;
                        position: relative;
                        top: -2px;
                    }

                    .certificate-title::before {
                        margin-right: 10px;
                    }

                    .certificate-title::after {
                        margin-left: 10px;
                    }

                    .cta-container {
                        text-align: center;
                        margin: 40px 0;
                    }

                    .cta-button {
                        display: inline-block;
                        padding: 16px 35px;
                        background: linear-gradient(135deg, #36b37e 0%, #1aae6f 100%);
                        color: #ffffff !important;
                        text-decoration: none;
                        font-weight: 600;
                        font-size: 16px;
                        border-radius: 50px;
                        box-shadow: 0 8px 15px rgba(54, 179, 126, 0.3);
                        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                        position: relative;
                        overflow: hidden;
                    }

                    .cta-button::before {
                        content: "";
                        position: absolute;
                        top: 0;
                        left: -100%;
                        width: 100%;
                        height: 100%;
                        background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
                        transition: all 0.6s ease;
                    }

                    .cta-button:hover {
                        transform: translateY(-3px) scale(1.02);
                        box-shadow: 0 12px 20px rgba(54, 179, 126, 0.4);
                    }

                    .cta-button:hover::before {
                        left: 100%;
                    }

                    .info-box {
                        background-color: #f8faff;
                        border-left: 4px solid #5e72e4;
                        padding: 25px;
                        margin: 35px 0;
                        border-radius: 0 12px 12px 0;
                        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
                        position: relative;
                        overflow: hidden;
                    }

                    .info-box::before {
                        content: "";
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background-image: url(\'data:image/svg+xml,%3Csvg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M0 0h20v20H0V0zm10 17a7 7 0 1 0 0-14 7 7 0 0 0 0 14z" fill="%235e72e4" fill-opacity="0.03" fill-rule="evenodd"/%3E%3C/svg%3E\');
                    }

                    .info-box h3 {
                        margin-top: 0;
                        color: #5e72e4;
                        font-size: 20px;
                        font-family: \'Playfair Display\', serif;
                        position: relative;
                    }

                    .info-box p {
                        margin-bottom: 0;
                        position: relative;
                    }

                    .info-box ul {
                        padding-left: 20px;
                        position: relative;
                    }

                    .info-box li {
                        margin-bottom: 8px;
                        position: relative;
                    }

                    .signature {
                        margin-top: 40px;
                        padding-top: 25px;
                        border-top: 1px solid #e2e8f0;
                    }

                    .signature p {
                        margin: 6px 0;
                    }

                    .team-name {
                        font-weight: 600;
                        color: #36b37e;
                        font-family: \'Playfair Display\', serif;
                        font-size: 18px;
                    }

                    .email-footer {
                        background-color: #f8faff;
                        padding: 30px 50px;
                        text-align: center;
                        color: #718096;
                        font-size: 14px;
                        border-top: 1px solid #e2e8f0;
                    }

                    .social-links {
                        margin: 25px 0;
                    }

                    .social-links a {
                        display: inline-block;
                        margin: 0 12px;
                        color: #5e72e4;
                        text-decoration: none;
                        transition: all 0.3s ease;
                        position: relative;
                    }

                    .social-links a::after {
                        content: "";
                        position: absolute;
                        bottom: -5px;
                        left: 0;
                        width: 0;
                        height: 1px;
                        background-color: #5e72e4;
                        transition: width 0.3s ease;
                    }

                    .social-links a:hover::after {
                        width: 100%;
                    }

                    .footer-links {
                        margin: 20px 0;
                    }

                    .footer-links a {
                        color: #5e72e4;
                        text-decoration: none;
                        margin: 0 12px;
                        transition: all 0.3s ease;
                        position: relative;
                    }

                    .footer-links a::after {
                        content: "";
                        position: absolute;
                        bottom: -5px;
                        left: 0;
                        width: 0;
                        height: 1px;
                        background-color: #5e72e4;
                        transition: width 0.3s ease;
                    }

                    .footer-links a:hover::after {
                        width: 100%;
                    }

                    .copyright {
                        margin-top: 20px;
                        font-size: 13px;
                        color: #a0aec0;
                    }

                    .confetti {
                        position: relative;
                        height: 80px;
                        margin: 20px 0;
                        text-align: center;
                        overflow: hidden;
                    }

                    .confetti span {
                        position: absolute;
                        font-size: 24px;
                        animation: fall 3s infinite;
                        filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.1));
                    }

                    .confetti span:nth-child(1) {
                        left: 10%;
                        animation-delay: 0.5s;
                    }

                    .confetti span:nth-child(2) {
                        left: 30%;
                        animation-delay: 1s;
                    }

                    .confetti span:nth-child(3) {
                        left: 50%;
                        animation-delay: 1.5s;
                    }

                    .confetti span:nth-child(4) {
                        left: 70%;
                        animation-delay: 2s;
                    }

                    .confetti span:nth-child(5) {
                        left: 90%;
                        animation-delay: 2.5s;
                    }

                    @keyframes fall {
                        0% {
                            top: 0;
                            opacity: 1;
                            transform: translateX(0) rotate(0deg);
                        }
                        100% {
                            top: 100%;
                            opacity: 0.3;
                            transform: translateX(20px) rotate(180deg);
                        }
                    }

                    @media screen and (max-width: 600px) {
                        .email-container {
                            margin: 20px auto;
                        }

                        .email-header, .email-body, .email-footer {
                            padding: 30px;
                        }

                        .email-header h1 {
                            font-size: 26px;
                        }

                        .greeting {
                            font-size: 24px;
                        }

                        .message {
                            font-size: 15px;
                        }

                        .cta-button {
                            padding: 14px 28px;
                            font-size: 15px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="email-container">
                    <div class="email-header">
                        <div class="logo">
                            <!-- Logo PharmaLearn -->
                            <svg width="180" height="40" viewBox="0 0 180 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 0C8.954 0 0 8.954 0 20C0 31.046 8.954 40 20 40C31.046 40 40 31.046 40 20C40 8.954 31.046 0 20 0ZM20 6C26.627 6 32 11.373 32 18C32 24.627 26.627 30 20 30C13.373 30 8 24.627 8 18C8 11.373 13.373 6 20 6Z" fill="white"/>
                                <path d="M50 10H58.5C63.5 10 67 13.5 67 18.5C67 23.5 63.5 27 58.5 27H50V10ZM58 23C61 23 63 21 63 18.5C63 16 61 14 58 14H54V23H58Z" fill="white"/>
                                <path d="M70 10H74V23H84V27H70V10Z" fill="white"/>
                                <path d="M87 10H101V14H91V16.5H99V20.5H91V23H101V27H87V10Z" fill="white"/>
                                <path d="M104 10H108V19C108 21.5 109.5 23 112 23C114.5 23 116 21.5 116 19V10H120V19C120 23.5 117 27 112 27C107 27 104 23.5 104 19V10Z" fill="white"/>
                                <path d="M123 10H127V27H123V10Z" fill="white"/>
                                <path d="M131 10H135V23H145V27H131V10Z" fill="white"/>
                                <path d="M147 10H151V16.5H159V10H163V27H159V20.5H151V27H147V10Z" fill="white"/>
                            </svg>
                        </div>
                        <h1>Félicitations ! Certificat Obtenu</h1>
                    </div>

                    <div class="email-body">
                        <div class="confetti">
                            <span>🎉</span>
                            <span>🎊</span>
                            <span>🏆</span>
                            <span>🎊</span>
                            <span>🎉</span>
                        </div>

                        <div class="greeting">Félicitations ' . $name . ' !</div>

                        <div class="message">
                            <p>Nous avons le plaisir de vous informer que vous avez obtenu un certificat pour avoir complété avec succès le cours suivant :</p>
                        </div>

                        <div class="certificate-box">
                            <div class="certificate-title">"' . $coursTitre . '"</div>
                            <p>Ce certificat atteste de vos compétences et connaissances acquises dans ce domaine.</p>
                        </div>

                        <div class="cta-container">
                            <a href="' . $certificateUrl . '" class="cta-button">Voir et télécharger mon certificat</a>
                        </div>

                        <div class="info-box">
                            <h3>Que faire avec votre certificat ?</h3>
                            <ul>
                                <li>Téléchargez-le et conservez-le dans vos documents personnels</li>
                                <li>Partagez-le sur votre profil LinkedIn ou autres réseaux professionnels</li>
                                <li>Incluez-le dans votre CV pour valoriser vos compétences</li>
                            </ul>
                        </div>

                        <div class="signature">
                            <p>Nous vous félicitons pour votre engagement et votre réussite !</p>
                            <p>Cordialement,</p>
                            <p class="team-name">L\'équipe PharmaLearn</p>
                        </div>
                    </div>

                    <div class="email-footer">
                        <div class="social-links">
                            <a href="#">Facebook</a>
                            <a href="#">Twitter</a>
                            <a href="#">LinkedIn</a>
                            <a href="#">Instagram</a>
                        </div>

                        <div class="footer-links">
                            <a href="#">Centre d\'aide</a>
                            <a href="#">Conditions d\'utilisation</a>
                            <a href="#">Politique de confidentialité</a>
                        </div>

                        <div class="copyright">
                            <p>© ' . date('Y') . ' PharmaLearn. Tous droits réservés.</p>
                            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            ';

            $emailObj = (new Email())
                ->from(new Address($this->senderEmail, 'PharmaLearn'))
                ->to($email)
                ->subject('Félicitations ! Vous avez obtenu un certificat pour le cours ' . $coursTitre)
                ->text($textContent)
                ->html($htmlContent);

            error_log($logPrefix . ' Email configuré, tentative d\'envoi...');

            $this->mailer->send($emailObj);

            error_log($logPrefix . ' ✅ Email de notification de certificat envoyé avec succès à ' . $email);

            return true;
        } catch (\Exception $e) {
            $errorMsg = $logPrefix . ' ERREUR lors de l\'envoi de l\'email de notification de certificat: ' . $e->getMessage();
            error_log($errorMsg);
            error_log($logPrefix . ' Trace: ' . $e->getTraceAsString());
            error_log($logPrefix . ' ❌ ÉCHEC de l\'envoi de l\'email de notification de certificat à ' . $email);
            error_log($logPrefix . ' ❌ Erreur: ' . $e->getMessage());

            // Si la configuration est null://null, afficher un message spécifique
            if (strpos($e->getMessage(), 'null://null') !== false) {
                error_log($logPrefix . ' ℹ️ La configuration MAILER_DSN est définie sur \'null://null\'. ' .
                     'Les emails ne sont pas réellement envoyés. Modifiez le fichier .env pour configurer un transport d\'email réel.');
            }

            // Relancer l'exception pour qu'elle soit gérée par l'appelant
            throw $e;
        }
    }
}
