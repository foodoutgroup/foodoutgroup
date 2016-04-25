<?php
namespace Food\UserBundle\Command;

use Food\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangePasswordToCorporateSendCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('user:corporate-password:send')
            ->setDescription('Send new generated password')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $em = $this->getContainer()->get('doctrine')->getManager();
            $userManager = $this->getContainer()->get('fos_user.user_manager');

            $userCollection = $em->getRepository('FoodUserBundle:User')->findBy(array('isBussinesClient' => 1));

            foreach ($userCollection as $user) {
                $newPassword = $this->generateNewPassword();

                $user->setPlainPassword($newPassword);
                $userManager->updatePassword($user);

                $this->sendNewPasswordToUser($user, $newPassword);
            }

        } catch (\Exception $e) {
            $output->writeln('[!] Error when sending new password to corporate user');
            $output->writeln('[!] Error: '.$e->getMessage());
            $output->writeln('[!] Trace: '."\n".$e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * @TODO: refactor to service
     *
     * @return string
     */
    public function generateNewPassword()
    {
        $lowerCaseChars = 'qwertyupasdfghjkzxcvbnm';
        $upperCaseChars = 'QWERTYUPASDFGHJKLZXCVBNM';
        $digitChars = '23456789';
        $extraSymbols = '!@#$%^&*{}[];:,.';

        $length = rand(9, 12);
        $newPassword = '';
        for ($i = 1; $i <= $length; ++$i) {
            $symbol = rand(1, 10);
            if ($symbol == 1) {
                $newPassword .= $extraSymbols[rand(0, strlen($extraSymbols) - 1)];
            } elseif ($symbol > 1 && $symbol <= 3) {
                $newPassword .= $digitChars[rand(0, strlen($digitChars) - 1)];
            } elseif ($symbol > 3 && $symbol <= 6) {
                $newPassword .= $lowerCaseChars[rand(0, strlen($lowerCaseChars) - 1)];
            } else {
                $newPassword .= $upperCaseChars[rand(0, strlen($upperCaseChars) - 1)];
            }
        }

        return $newPassword;
    }

    /**
     * @param User $user
     * @param $password
     */
    public function sendNewPasswordToUser(User $user, $password)
    {
        $ml = $this->getContainer()->get('food.mailer');

        $variables = array(
            'password' => $password
        );

        $template = $this->getContainer()->getParameter('mailer_send_corporate_changed_password');

        $ml->setVariables($variables)
            ->setRecipient(
                $user->getEmail()
            )
            ->setId($template)
            ->send();
    }

}
