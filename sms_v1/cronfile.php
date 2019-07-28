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
$sql = "SELECT * FROM tbl_sms";
if ($result = $conn->query($sql)){
	ini_set('max_execution_time', 300);
	set_time_limit(300);

	for ($i=0 ; $i<=mysqli_num_rows($result) ; $i++){
		$sql1= "SELECT * FROM tbl_sms WHERE id=".$i;
		if ($result1=$conn->query($sql1)){
			while ($row = $result1->fetch_assoc()){
				//select unsent messages
				if ($row['serverstatus']==0){
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
						$sql = "UPDATE tbl_sms SET ".$serverStatus." WHERE id=".$row['id'];
						if ($conn->query($sql) === TRUE){
							// echo successfully sent
						}
					} 
				}
		}
	}
}
}

 ?>