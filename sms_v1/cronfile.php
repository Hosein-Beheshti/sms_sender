<?php 

 /**
 * @category   sms sender
 * @author   Hosein_Beheshti
 * you must use this file with Cron JOB to send the unsend messages periodically 
 */
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use mysqli;
use Exception;
	
	//database specification
 	$servername = "localhost";
	$username = "root";
	$password = "";
	$databaseName = "db_sms";

	$conn = new mysqli($servername , $username , $password , $databaseName );
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

		$sql1= "SELECT id,mobile,message,serverstatus FROM tbl_sms WHERE serverstatus=0";
		if ($result1=$conn->query($sql1)){
			ini_set('max_execution_time', 300);
			set_time_limit(300);
			while ($row = $result1->fetch_assoc()){
				//select unsent messages
					if($row['serverstatus']==0){
					$serverStatus =0;

					//try to send again
					try{
						$ch = curl_init();

						curl_setopt($ch, CURLOPT_URL, 'localhost:81/body='.$row['message'].'/send/?number='.$row['mobile']);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

						$result3 = curl_exec($ch);
						if (!$result3){
							throw new Exception("Error Processing Request", 1);
						} else{
							$serverStatus=1;
						}

						curl_close($ch);

					} catch (Exception $e){
						try{
							$ch = curl_init();

							curl_setopt($ch, CURLOPT_URL, 'localhost:82/body='.$row['message'].'/send/?number='.$row['mobile']);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

							$result4 = curl_exec($ch);
							if (!$result4){
								throw new Exception("Error Processing Request", 1);
							} else{
								$serverStatus=2;
							}
						} catch(Exception $e){
							$serverStatus=0;
						}
						curl_close($ch);
					}

					//if message is sent with API_1 or API_2 change the server status
					if ($serverStatus!=0){
						echo "updated ";
						$sql = "UPDATE tbl_sms SET serverstatus=".$serverStatus." WHERE id=".$row['id'];
						if ($conn->query($sql) === TRUE){
							// echo successfully sent
						}
					} 
		}
	}
	}

 ?>