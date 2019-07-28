<?php 
 /**
 * @category   sms sender
 * @author   Hosein Beheshti
 */
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use mysqli;
use Thread;
use Exception;


/** 
* SendingController is a class that include 
* welcomePage() and sendSmsAction() functions
* for send messages
*/
class SendingController extends Controller
{
	/**
	 * @Route("/")
	 *
	 * this function returns welcome page
	 * @return html file
	 */
    public function welcomePage()
    {
        return $this->render('view/welcome.html.twig');
    }
    /**
	 * @Route("/send")
	 *
	 * this function get phone number and message 
	 * and try to send its by our APIs
	 * @param $request is an object
	 * @return html file
	 */
    public function sendSmsAction(Request $request)
    {
    //geting phone number and text message from request that sent from our welcome page
	$number = $request->get('Number');
	$message = $request->get('Message');
	//echo "number: " . $number;   
	//echo "message: " . $message; 

	//$servers is an integer that show server status and it can have 3 values
	//0 -> is not sent
	//1 -> sent by  API_1
	//2 -> API_1 faulted and sent by API_2
	$servers = 0;

	//send request to our APIs and return an exeption if oth APIs is not available
	try{
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, 'localhost:81/body='.$message.'/send/?number='.$number);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);
			if (!$result){
				throw new Exception("Error Processing Request", 1);
			} else{
				$servers=1;
			}



		} catch (Exception $e){
			try{
				$ch = curl_init();

				curl_setopt($ch, CURLOPT_URL, 'localhost:82/body='.$message.'/send/?number='.$number);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

				$result = curl_exec($ch);
				if (!$result){
					throw new Exception("Error Processing Request", 1);
				} else{
					$servers=2;
				}
			} catch(Exception $e){
				$servers=0;
			}
			curl_close($ch);
		}
	//instants of DatabaseController class
	$databaseController = new DatabaseController();
	//call insert function and passing variables
	$databaseController->insert($number , $message , $servers);



        return $this->render('view/welcome.html.twig');
   	} 
}
 ?>