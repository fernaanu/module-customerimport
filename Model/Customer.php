<?php
/**
 * Wunderman Thompson Ltd.
 *
 * @category    WT
 * @package     CustomerImport
 * @author      Anuradha Fernando <anuradhafernando81@gmail.com>
 * @copyright   Copyright (c) 2022 Wunderman Thompson Ltd. (https://www.wundermanthompson.com)
 */
namespace WT\CustomerImport\Model;

use Exception;
use Generator;
use \Magento\Framework\Filesystem\Io\File;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Customer\Api\Data\CustomerInterfaceFactory;
use \Magento\Customer\Api\CustomerRepositoryInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Customer
 */
class Customer
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
     * @var
     */
    private $output;

    /**
     * @var CustomerInterfaceFactory
     */
    protected $customerInterfaceFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * Construct
     *
     * @param File $file
     * @param StoreManagerInterface $storeManagerInterface
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     */
    public function __construct(
        File $file,
        StoreManagerInterface $storeManagerInterface,
        CustomerInterfaceFactory $customerInterfaceFactory,
        CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        $this->file = $file;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->customerInterfaceFactory = $customerInterfaceFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
    }

    /**
     * Save customer data
     *
     * @param string $profile
     * @param string $source
     * @param OutputInterface $output
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function saveCustomerData(string $profile, string $source, OutputInterface $output)
    {
        $this->output = $output;
        $store = $this->storeManagerInterface->getStore();

        //Default Website Id
        $websiteId = (int) $this->storeManagerInterface->getWebsite()->getId();
        //Default Store Id
        $storeId = $store->getId();

        //Supported media types.
        //We can add a multiple selection to the admin as a config option
        $mediaTypes = ['csv', 'json', 'xml'];

        $profileType = explode('-', $profile);

        if (in_array(end($profileType), $mediaTypes)) {
            switch (end($profileType))
            {
                case 'csv':
                    $csvHeader = $this->readCsvHeaders($source)->current();
                    $CsvRow = $this->readCsvRows($source, $csvHeader);
                    $CsvRow->next();

                    while ($CsvRow->valid()) {
                        $data = $CsvRow->current();
                        $this->buildCustomerData($data, $websiteId, $storeId);
                        $CsvRow->next();
                    }
                    break;

                case 'json':
                    $store = $this->storeManagerInterface->getStore();
                    $websiteId = (int) $this->storeManagerInterface->getWebsite()->getId();
                    $storeId = $store->getId();

                    //get JSON data as array
                    $jsonData = json_decode(file_get_contents($source), true);
                    foreach ($jsonData as $data) {
                        $this->buildCustomerData($data, $websiteId, $storeId);
                    }
                    break;

                case 'xml':
                    //Not implemented yet
                    $this->output->writeln(
                        '<error>' . end($profileType) . ': Not Implemented yet.</error>',
                        OutputInterface::OUTPUT_NORMAL
                    );
                    break;
            }
        } else {
            $this->output->writeln(
                '<error>' . end($profileType) . ': Unsupported profile type</error>',
                OutputInterface::OUTPUT_NORMAL
            );
        }
    }

    /**
     * Buils customer data
     *
     * @param array $data
     * @param int $websiteId
     * @param int $storeId
     * @return bool|void
     */
    private function buildCustomerData(array $data, int $websiteId, int $storeId)
    {
        try {
            $customer = $this->customerInterfaceFactory->create();

            //Assign customer data to customer fields
            $customer->setEmail($data['emailaddress']);
            $customer->setFirstname($data['fname']);
            $customer->setLastname($data['lname']);
            $customer->setWebsiteId($websiteId);
            $customer->setStoreId($storeId);
            $customer->setGroupId(1);
            $saveCustomer = $this->customerRepositoryInterface->save($customer);
            if ($saveCustomer) {
                return true;
            }
        } catch (Exception $e) {
            $this->output->writeln(
                '<error>'. $e->getMessage() .'</error>',
                OutputInterface::OUTPUT_NORMAL
            );
        }
    }

    /**
     * Read Csv rows
     *
     * @param string $file
     * @param array $header
     * @return Generator|null
     */
    private function readCsvRows(string $file, array $header): ?Generator
    {
        $fp = fopen($file, 'rb');

        while (!feof($fp)) {
            $data = [];
            $rowData = fgetcsv($fp);
            if ($rowData) {
                foreach ($rowData as $key => $value) {
                    $data[$header[$key]] = $value;
                }
                yield $data;
            }
        }

        fclose($fp);
    }

    /**
     * Read Csv headers
     *
     * @param string $file
     * @return Generator|null
     */
    private function readCsvHeaders(string $file): ?Generator
    {
        $fp = fopen($file, 'rb');

        while (!feof($fp)) {
            yield fgetcsv($fp);
        }

        fclose($fp);
    }
}
