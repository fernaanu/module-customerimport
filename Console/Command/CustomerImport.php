<?php
/**
 * Wunderman Thompson Ltd.
 *
 * @category    WT
 * @package     CustomerImport
 * @author      Anuradha Fernando <anuradhafernando81@gmail.com>
 * @copyright   Copyright (c) 2022 Wunderman Thompson Ltd. (https://www.wundermanthompson.com)
 */
namespace WT\CustomerImport\Console\Command;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WT\CustomerImport\Model\Customer;

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
    private $customer;

    /**
     * Construct
     * @param Filesystem $filesystem
     * @param Customer $customer
     */
    public function __construct(
        Filesystem $filesystem,
        Customer $customer
    )
    {
        $this->filesystem = $filesystem;
        $this->customer = $customer;
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
        $this->setDescription('Customer data import from the CSV, JSON AND XML');

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
     * @return int|void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $profile = $input->getArgument('profile');
        $source = $input->getArgument('source');

        $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $sourcePath = $mediaDir->getAbsolutePath() . 'import/' . $source;

        $saveData = $this->customer->saveCustomerData($profile, $sourcePath, $output);

        if ($saveData) {
            $output->writeln(
                '<info>Customer import completed</info>',
                OutputInterface::OUTPUT_NORMAL
            );
        }

    }
}
