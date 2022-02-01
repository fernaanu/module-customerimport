<?php
/**
 * Wunderman Thompson Ltd.
 *
 * @category    WT1
 * @package     CustomerImport
 * @author      Anuradha Fernando <anuradhafernando81@gmail.com>
 * @copyright   Copyright (c) 2022 Wunderman Thompson Ltd. (https://www.wundermanthompson.com)
 */
namespace WT1\CustomerImport\Console\Command;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WT1\CustomerImport\Model\ImportCustomer;

/**
 * Class Customer Import by CSV
 */
class CustomerImport extends Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    private $filesystem;

    /**
     * @var Customer
     */
    private $importCustomer;

    /**
     * Construct
     * @param Filesystem $filesystem
     * @param ImportCustomer $importCustomer
     */
    public function __construct(
        Filesystem $filesystem,
        ImportCustomer $importCustomer
    )
    {
        $this->filesystem = $filesystem;
        $this->importCustomer = $importCustomer;
        parent::__construct();
    }

    /**
     * CLI Configuration
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('wt:customer:import');
        $this->setDescription('Customer data import from the CSV and JSON');

        //Set arguments - passing parameters
        $this->addArgument('profile', InputArgument::REQUIRED, __('Profile Name'));
        $this->addArgument('source', InputArgument::REQUIRED, __('Source File Name'));

        parent::configure();
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $profile = $input->getArgument('profile');
        $source = $input->getArgument('source');

        $customerImport = $this->importCustomer->saveCustomerData($profile, $source, $output);
        if ($customerImport) {
            $output->writeln("Customer data import completed. Please check logs for more details.");
        }
    }
}
