<?php
namespace App\Command;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
class WebhookCommand extends Command
{
	protected static $defaultName = 'app:webhook';
	
	/**
	 * @var LoggerInterface $logger
	 */
	protected $logger;
	
	public function __construct(LoggerInterface $logger) {
		parent::__construct();
		
		$this->logger = $logger;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::configure()
	 */
	protected function configure() {
		$this->setName(static::$defaultName)
			 ->setDefinition([
			 		new InputOption('event-type', null, InputOption::VALUE_REQUIRED, 'The webhook event type.'),
			 		new InputOption('event-version', null, InputOption::VALUE_REQUIRED, 'The webhook event version.', '1'),
					new InputOption('source-url', 's', InputOption::VALUE_REQUIRED, 'The source URL.'),
			 		new InputOption('filename', 'f', InputOption::VALUE_OPTIONAL, 'The filename path.'),
			 		new InputArgument('url', InputArgument::REQUIRED, 'The notify URL.')
			 ])
			 ->setDescription('Command permit of trigger a webhook')
			 ->setHelp(<<<EOF
The <info>%command.name%</info> command permit of trigger a webhook :

  <info>php %command.full_name% --event-type="DOWNLOAD.SUCCESSFULLY" --source-url="http://www.exemple.com/index.php" "http://exemple.com/webhook/listen"</info>

This interactive shell will ask you for at filename.

You can specify filename :

  <info>php %command.full_name% --event-type="DOWNLOAD.SUCCESSFULLY" --source-url="http://www.exemple.com/index.php" --filename="Jeudi_c_est_Koulibaly_Les_lecons_des_elections_municipales-1549652437.mp4" "http://exemple.com/webhook/listen"</info>
EOF
				);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::interact()
	 */
	protected function interact(InputInterface $input, OutputInterface $output) {
		/** @var \Symfony\Component\Console\Helper\QuestionHelper $questionHelper */
		$questionHelper = $this->getHelper('question');
		$questions = [];
		
		if (!$input->getArgument('url')) {
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
		
		if (!$input->getOption('event-type')) {
			$question = new Question('Please choose a event type:');
			$question->setValidator(function($process){
				if (true === empty($process)) {
					throw new \Exception('Event type can not be empty');
				}
				return $process;
			});
			$questions['event-type'] = $question;
		}
		
		if (!$input->getOption('source-url')) {
			$question = new Question('Please choose a source url:');
			$question->setValidator(function($process){
				if (true === empty($process)) {
					throw new \Exception('Source URL can not be empty');
				}
				return $process;
			});
			$questions['source-url'] = $question;
		}
		
		foreach ($questions as $key => $question) {
			$answer = $questionHelper->ask($input, $output, $question);
			$input->setOption($key, $answer);
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$body = [
				'createdAt' => date('c'),
				'eventType' => $input->getOption('event-type'),
				'eventVersion' => $input->getOption('event-version'),
				'resource' => [
						'sourceUrl' => $input->getOption('source-url')
				]
		];
		
		if (null !== $input->getOption('filename')) {
			$body['resource']['filename'] = basename($input->getOption('filename'));
		}
		
		try {
			(new Client())->post($input->getArgument('url'), [RequestOptions::JSON => $body]);
		} catch (\Exception $e) {
			$this->logger->error(sprintf(
					'%s: %s (uncaught exception) at %s line %s',
					get_class($e),
					$e->getMessage(),
					$e->getFile(),
					$e->getLine()
			), $body);
		}
	}
}
