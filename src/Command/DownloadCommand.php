<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Question\Question;

class DownloadCommand extends Command
{
	protected static $defaultName = 'app:download';
	
	/**
	 * @var string $binDir
	 */
	protected $binDir;
	
	public function __construct($binDir) {
		parent::__construct();
		
		$this->binDir = $binDir;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::configure()
	 */
	protected function configure() {
		$this->setName(static::$defaultName)
			 ->setDefinition([
			 		new InputOption('extract-audio', 'x', InputOption::VALUE_NONE, 'Convert video files to audio-only files.'),
			 		new InputOption('audio-format', 'a', InputOption::VALUE_OPTIONAL, 'Specify audio format: "best", "aac", "flac", "mp3", "m4a", "opus", "vorbis", or "wav"; "best" by default; No effect without -x.', 'mp3'),
			 		new InputOption('redirect-url', 'r', InputOption::VALUE_OPTIONAL, 'Trigger webhooks to inform download state.'),
			 		new InputArgument('url', InputArgument::REQUIRED, 'The download URL.')
			 ])
			 ->setDescription('Command permit of a download URL')
			 ->setHelp(<<<EOF
The <info>%command.name%</info> command permit of download URL :

  <info>php %command.full_name% https://www.youtube.com/watch?v=2ESAi2vq-80</info>

This interactive shell will ask you for at URL.

You can specify extract-audio option for convert video files to audio-only files :

  <info>php %command.full_name% --x --audio-format=mp3 https://www.youtube.com/watch?v=2ESAi2vq-80</info>

You can specify redirect url :

  <info>php %command.full_name% --redirect-url=http://www.exemple.com/index.php https://www.youtube.com/watch?v=2ESAi2vq-80</info>
EOF
				);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::interact()
	 */
	protected function interact(InputInterface $input, OutputInterface $output) {
		if (!$input->getArgument('url')) {
			/** @var \Symfony\Component\Console\Helper\QuestionHelper $questionHelper */
			$questionHelper = $this->getHelper('question');
			$question = new Question('Please choose a url:');
			$question->setValidator(function($process){
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
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$options = [];
		$commands = ['chmod -R 0755 {}'];
		$output = new StreamOutput(fopen('php://stdout', 'w'));
		
		if (true === $input->getOption('extract-audio')) {
			$options[] = '-x';
			$options[] = sprintf('--audio-format %s', $input->getOption('audio-format'));
		}
		
		if (null !== $input->getOption('redirect-url')) {
			$commands[] = sprintf('php %s/console %s --event-type="DOWNLOAD.SUCCESSFULLY" --source-url="%s" --filename="{}" "%s"', $this->binDir, WebhookCommand::getDefaultName(), $input->getArgument('url'), $input->getOption('redirect-url'));
		}
		
		$out = $status = null;
		$command = sprintf('youtube-dl -f best --audio-quality 0 --restrict-filenames --yes-playlist %s --exec \'%s\' \'%s\' &', implode(' ', $options), implode(' && ', $commands), $input->getArgument('url'));
		exec($command, $out, $status);
		
		if ((int) $status > 0 && null !== $input->getOption('redirect-url')) {
			$input = new ArrayInput([
					'--event-type' => 'DOWNLOAD.FAILED',
					'--source-url' => $input->getArgument('url'),
					'url' => $input->getOption('redirect-url')
			]);
			
			$command = $this->getApplication()->find(WebhookCommand::getDefaultName());
			$command->run($input, $output);
		}
	}
}
