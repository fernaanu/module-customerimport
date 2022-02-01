<?php
/**
 * Wunderman Thompson Ltd.
 *
 * @category    WT1
 * @package     CustomerImport
 * @author      Anuradha Fernando <anuradhafernando81@gmail.com>
 * @copyright   Copyright (c) 2022 Wunderman Thompson Ltd. (https://www.wundermanthompson.com)
 */
namespace WT1\CustomerImport\Model;

use \Magento\Framework\Filesystem\Io\File;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Customer\Model\CustomerFactory;
use Symfony\Component\Console\Output\OutputInterface;
use \Psr\Log\LoggerInterface;

/**
 * Class Customer
 */
class ImportCustomer
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var StoreManagerInterface
     */
    private $storeManagerInterface;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Default import dir
     */
    const IMPORT_DIR = 'var/import/';

    /**
     * Construct
     *
     * @param File $file
     * @param StoreManagerInterface $storeManagerInterface
     * @param CustomerFactory $customerFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        File $file,
        StoreManagerInterface $storeManagerInterface,
        CustomerFactory $customerFactory,
        LoggerInterface $logger
    ) {
        $this->file = $file;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->customerFactory = $customerFactory;
        $this->logger = $logger;
    }

    /**
     * Save customer data
     *
     * @param string $profile
     * @param string $source
     * @param OutputInterface $output
     * @return bool|void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function saveCustomerData(string $profile, string $source, OutputInterface $output)
    {
        $error = 0;
        $this->output = $output;

        //Supported media types.
        //We can add a multiple selection to the admin as a config option
        $mediaTypes = ['csv', 'json'];

        $profileType = explode('-', $profile);

        if (in_array(end($profileType), $mediaTypes)) {
            switch (end($profileType))
            {
                case 'csv':
                    $importCsv = $this->importCustomerCsv($source);
                    if ($importCsv) {
                        $error = 0;
                    }
                    break;

                case 'json':
                    $importJson = $this->importCustomerJson($source);
                    if ($importJson) {
                        $error = 0;
                    }
                    break;

                case 'xml':
                    //Not implemented yet
                    $this->logger->warning(end($profileType) . ": Unsupported profile type");
                    $this->output->writeln(
                        '<error>' . end($profileType) . ': Unsupported profile type</error>',
                        OutputInterface::OUTPUT_NORMAL
                    );
                    break;
            }

            if ($error == 0) {
                return true;
            }
        } else {
            $this->output->writeln(
                '<error>' . end($profileType) . ': Unsupported profile type</error>',
                OutputInterface::OUTPUT_NORMAL
            );
        }
    }

    /**
     * Customer data Csv import
     *
     * @param $source
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function importCustomerCsv($source)
    {
        $this->logger->info("CSV import started.");
        $store = $this->storeManagerInterface->getStore();
        $websiteId = (int) $this->storeManagerInterface->getWebsite()->getId();
        $storeId = $store->getId();

        //Get Csv data from source file
        $fp = fopen(self::IMPORT_DIR . $source, 'r');
        if ($fp !== false)
        {
            $headers = fgetcsv($fp);
            while ($rows = fgetcsv($fp, 4000, ","))
            {
                $rowCount = count($rows);
                if ($rowCount < 1)
                {
                    continue;
                }

                $customerData = array_combine($headers, $rows);

                $customerObj = $this
                    ->customerFactory
                    ->create()
                    ->setWebsiteId($websiteId);
                $customer = $customerObj->loadByEmail($customerData['emailaddress']);
                try
                {
                    $customer
                        ->setEmail($customerData['emailaddress'])
                        ->setFirstname($customerData['fname'])
                        ->setLastname($customerData['lname'])
                        ->setWebsiteId($websiteId)
                        ->setStoreId($storeId)
                        ->setGroupId(1);

                    $customAttribute = $customer->getDataModel();
                    $customer->updateData($customAttribute);
                    $customer->save();
                    $this->logger->info("CSV import completed.");
                }
                catch(\Exception $e)
                {
                    $this->logger->warning("CSV import failed | " . $e->getMessage());
                    $this->output->writeln(
                        '<error>'. $e->getMessage() .'</error>',
                        OutputInterface::OUTPUT_NORMAL
                    );
                }
            }
        }
    }

    /**
     * Customer data Json import
     *
     * @param $source
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function importCustomerJson($source)
    {
        $this->logger->info("JSON import started.");
        $store = $this->storeManagerInterface->getStore();
        $websiteId = (int)$this->storeManagerInterface->getWebsite()->getId();
        $storeId = $store->getId();

        $jsonFilePath = self::IMPORT_DIR . $source;

        if ($jsonFilePath != false) {
            $jsonData = json_decode(file_get_contents($jsonFilePath), true);
            foreach ($jsonData as $data) {
                $customerObj = $this
                    ->customerFactory
                    ->create()
                    ->setWebsiteId($websiteId);

                $customer = $customerObj->loadByEmail($data['emailaddress']);
                try {
                    $customer
                        ->setEmail($data['emailaddress'])
                        ->setFirstname($data['fname'])
                        ->setLastname($data['lname'])
                        ->setWebsiteId($websiteId)
                        ->setStoreId($storeId)
                        ->setGroupId(1);

                    $customAttribute = $customer->getDataModel();
                    $customer->updateData($customAttribute);
                    $customer->save();
                    $this->logger->info("JSON import completed.");
                } catch (\Exception $e) {
                    $this->logger->warning("JSON import failed | " . $e->getMessage());
                    $this->output->writeln(
                        '<error>' . $e->getMessage() . '</error>',
                        OutputInterface::OUTPUT_NORMAL
                    );
                }
            }
        }
    }
}
