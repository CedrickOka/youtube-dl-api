<?php

namespace App\Command;

use App\Service\WebhookManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 */
class WebhookCommand extends Command
{
    protected static $defaultName = 'app:webhook';

    protected $webhookManager;

    public function __construct(WebhookManager $webhookManager)
    {
        parent::__construct();

        $this->webhookManager = $webhookManager;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this->setName(static::$defaultName)
             ->setDefinition([
                new InputOption('eventType', null, InputOption::VALUE_REQUIRED, 'The webhook event type.'),
                new InputOption('eventVersion', null, InputOption::VALUE_REQUIRED, 'The webhook event version.', '1'),
                new InputOption('sourceUrl', 's', InputOption::VALUE_REQUIRED, 'The source URL.'),
                new InputOption('filename', 'f', InputOption::VALUE_OPTIONAL, 'The filename path.'),
                new InputArgument('url', InputArgument::REQUIRED, 'The notify URL.'),
             ])
             ->setDescription('Command permit of trigger a webhook')
             ->setHelp(<<<EOF
The <info>%command.name%</info> command permit of trigger a webhook :

  <info>php %command.full_name% --eventType="DOWNLOAD.SUCCESSFULLY" --sourceUrl="http://www.exemple.com/index.php" "http://exemple.com/webhook/listen"</info>

This interactive shell will ask you for at filename.

You can specify filename :

  <info>php %command.full_name% --eventType="DOWNLOAD.SUCCESSFULLY" --sourceUrl="http://www.exemple.com/index.php" --filename="Jeudi_c_est_Koulibaly_Les_lecons_des_elections_municipales-1549652437.mp4" "http://exemple.com/webhook/listen"</info>
EOF
                );
    }

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::interact()
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        /** @var \Symfony\Component\Console\Helper\QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');
        $questions = [];

        if (!$input->getArgument('url')) {
            $question = new Question('Please choose a url:');
            $question->setValidator(function ($process) {
                if (true === empty($process)) {
                    throw new \Exception('URL can not be empty');
                }

                return $process;
            });

            $answer = $questionHelper->ask($input, $output, $question);
            $input->setArgument('url', $answer);
        }

        if (!$input->getOption('eventType')) {
            $question = new Question('Please choose a event type:');
            $question->setValidator(function ($process) {
                if (true === empty($process)) {
                    throw new \Exception('Event type can not be empty');
                }

                return $process;
            });
            $questions['eventType'] = $question;
        }

        if (!$input->getOption('sourceUrl')) {
            $question = new Question('Please choose a source url:');
            $question->setValidator(function ($process) {
                if (true === empty($process)) {
                    throw new \Exception('Source URL can not be empty');
                }

                return $process;
            });
            $questions['sourceUrl'] = $question;
        }

        foreach ($questions as $key => $question) {
            $answer = $questionHelper->ask($input, $output, $question);
            $input->setOption($key, $answer);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->webhookManager->send(
            $input->getArgument('url'),
            $input->getOption('eventType'),
            $input->getOption('eventVersion'),
            $input->getOption('sourceUrl'),
            $input->getOption('filename')
        );

        return 0;
    }
}
