<?php

namespace App\Command;

use App\Service\DownloadManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 */
class DownloadCommand extends Command
{
    protected static $defaultName = 'app:download';

    protected $downloadManager;

    public function __construct(DownloadManager $downloadManager)
    {
        parent::__construct();

        $this->downloadManager = $downloadManager;
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
                 new InputOption('proxyUrl', null, InputOption::VALUE_OPTIONAL, 'Use the specified HTTP/HTTPS/SOCKS proxy.'),
                 new InputOption('eventUrl', null, InputOption::VALUE_OPTIONAL, 'Trigger webhooks to inform download state.'),
                 new InputOption('extractAudio', null, InputOption::VALUE_NONE, 'Convert video files to audio-only files.'),
                 new InputOption('audioFormat', null, InputOption::VALUE_OPTIONAL, 'Specify audio format: "best", "aac", "flac", "mp3", "m4a", "opus", "vorbis", or "wav"; "best" by default; No effect without -x.', 'mp3'),
                 new InputOption('unixOwner', null, InputOption::VALUE_OPTIONAL, 'The unix owner of the file to download.'),
                 new InputArgument('url', InputArgument::REQUIRED, 'The download URL.'),
             ])
             ->setDescription('Command permit of a download URL')
             ->setHelp(<<<EOF
The <info>%command.name%</info> command permit of download URL :

  <info>php %command.full_name% https://www.youtube.com/watch?v=2ESAi2vq-80</info>

This interactive shell will ask you for at URL.

You can specify extract-audio option for convert video files to audio-only files :

  <info>php %command.full_name% --x --audioFormat=mp3 https://www.youtube.com/watch?v=2ESAi2vq-80</info>

You can specify event url :

  <info>php %command.full_name% --eventUrl=http://www.exemple.com/index.php https://www.youtube.com/watch?v=2ESAi2vq-80</info>

You can specify file unix owner :

  <info>php %command.full_name% --unixOwner=www-data https://www.youtube.com/watch?v=2ESAi2vq-80</info>
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
        if (!$input->getArgument('url')) {
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $questionHelper */
            $questionHelper = $this->getHelper('question');
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
    }

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = ['extractAudio' => $input->getOption('extractAudio')];

        if (null !== $input->getOption('proxyUrl')) {
            $options['proxyUrl'] = $input->getOption('proxyUrl');
        }
        if (null !== $input->getOption('eventUrl')) {
            $options['eventUrl'] = $input->getOption('eventUrl');
        }
        if (null !== $input->getOption('audioFormat')) {
            $options['audioFormat'] = $input->getOption('audioFormat');
        }
        if (null !== $input->getOption('unixOwner')) {
            $options['unixOwner'] = $input->getOption('unixOwner');
        }

        $this->downloadManager->execute($input->getArgument('url'), $options);

        return 0;
    }
}
