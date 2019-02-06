<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class DownloadCommand extends Command
{
	protected static $defaultName = 'app:download';
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::configure()
	 */
	protected function configure() {
		$this->setName(static::$defaultName)
			 ->setDefinition([
			 		new InputOption('extract-audio', 'x', InputOption::VALUE_NONE, 'Convert video files to audio-only files.'),
			 		new InputOption('audio-format', null, InputOption::VALUE_OPTIONAL, 'Specify audio format: "best", "aac", "flac", "mp3", "m4a", "opus", "vorbis", or "wav"; "best" by default; No effect without -x.', 'mp3'),
			 		new InputOption('redirect-url', null, InputOption::VALUE_OPTIONAL, 'Trigger webhooks to inform download state.'),
			 		new InputArgument('url', InputArgument::REQUIRED, 'The download URL.')
			 ])
			 ->setDescription('Command permit of a download URL')
			 ->setHelp(<<<EOF
The <info>%command.name%</info> command permit of download URL :

  <info>php %command.full_name% 1</info>

This interactive shell will ask you for at URL.
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
		
		if (true === $input->getOption('extract-audio')) {
			$options[] = '-x';
			$options[] = sprintf('--audio-format %s', $input->getOption('audio-format'));
		}
		
		if (true === $input->hasOption('redirect-url')) {
			$commands[] = sprintf('curl -X POST %s -H \'Accept: application/json\' -H \'Content-Type: application/json\' -d \'{"filename": "{}"}\'', $input->getOption('redirect-url'));
		}
		
		$shellOutput = $shellReturn = '';
		exec(sprintf('youtube-dl -f best --audio-quality 0 %s --exec \'%s\' %s >> /dev/stdout 2>> /dev/stderr', implode(' ', $options), implode(' && ', $commands), $input->getArgument('url')), $shellOutput, $shellReturn);
		
		$output->writeln($shellOutput);
		$output->writeln($shellReturn);
	}
}
