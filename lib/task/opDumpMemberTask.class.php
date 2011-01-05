<?php
/**
 * opDumpMemberTask
 *
 * @package    opCSvUtilPlugin
 * @subpackage task
 * @author     hidenorigoto <hidenorigoto@gmail.com>
 */
class opDumpMemberTask extends sfBaseTask
{
  /**
   * opDumpMemberTask::configure()
   *
   * @return
   */
  protected function configure()
  {
    //$this->addArguments(array(
    //  new sfCommandArgument('member_id',  sfCommandArgument::REQUIRED, 'member id'),
    //  new sfCommandArgument('config_key', sfCommandArgument::REQUIRED, 'config key'),
    //));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env',         null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection',  null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'doctrine'),
    ));

    $this->namespace           = 'openpne';
    $this->name                = 'csv-dump-member';
    $this->briefDescription    = '';
    $this->detailedDescription = <<<EOF
The [openpne:csv-dump-member|INFO] task dumps member list data to the /data/memberlist.csv.
Call it with:

  [php symfony openpne:csv-dump-member|INFO]
EOF;
  }

  /**
   * opDumpMemberTask::execute()
   *
   * @param array $arguments
   * @param array $options
   * @return
   */
  protected function execute($arguments = array(), $options = array())
  {
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();
    $doctrineConnection = $databaseManager->getDatabase($options['connection'])->getDoctrineConnection();

    $fp = fopen(sfConfig::get('sf_data_dir') . '/memberlist.csv', 'w');

    // gets member list(using pure SQL)
    $sql = 'select * from member';
    $members = $doctrineConnection->fetchAll($sql, array());
    foreach ($members as $member)
    {
        $line = array();
        $memberObj = new Member();
        $memberObj->fromArray($member);

        // nickname is required
        $name = $memberObj->getName();
        if (empty($name))
        {
            continue;
        }

        $line[] = $memberObj->getId();
        $line[] = $name;

        $email = trim($memberObj->getConfig('pc_address'));
        if (empty($email))
        {
            $email = $memberObj->getConfig('mobile_address');
        }
        if (empty($email))
        {
            continue;
        }
        $line[] = trim($email);

        fputcsv($fp, $line, ',', '"');
    }
    fclose($fp);
  }
}
