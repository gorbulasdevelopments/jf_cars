<?php

class vehicle_Model extends Model {

    public function __construct() {
        parent::__construct();
    }
    
    public function getAllSales() {
        $strSQL = $this->db->prepare("SELECT st.sale_add_date, st.sale_price, sale_author, sale_complete_date, vehicle_registration, vehicle_make, vehicle_model, vehicle_colour from sale_table st join vehicle_table vt on st.vehicle_id = vt.vehicle_id;");
        $strSQL->execute();
        return $strSQL->fetchAll(PDO::FETCH_ASSOC);
    }
	
	public function getAllVehicles() {
        $strSQL = $this->db->prepare("SELECT *, (SELECT COUNT(*) from vehicle_image_table where vehicle_id = vt.vehicle_id) as vehicle_image_count from vehicle_table vt;");
        $strSQL->execute();
        return $strSQL->fetchAll(PDO::FETCH_ASSOC);
    }
	
	public function getVehicleDetails($vehicleRegistration) {
		$strSQL = $this->db->prepare("SELECT * from vehicle_table where vehicle_registration = '" . $vehicleRegistration . "'");
        $strSQL->execute();
        return $strSQL->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function getVehicleImages($vehicleRegistration) {
		//$strSQL = $this->db->prepare("SELECT DISTINCT vt.vehicle_registration, vt.vehicle_make, vt.vehicle_model, vit.vehicle_image_url, vit.vehicle_image_priority, vehicle_cover_image from (vehicle_table vt join sale_table st on st.vehicle_id = vt.vehicle_id) join vehicle_image_table vit on vt.vehicle_id = vit.vehicle_id WHERE vt.vehicle_registration = '" . $vehicleRegistration . "' ORDER BY vit.vehicle_cover_image DESC,CASE WHEN vit.vehicle_cover_image THEN vit.vehicle_image_priority  ELSE vit.vehicle_image_priority END ASC;");
		$strSQL = $this->db->prepare("SELECT vt.vehicle_registration, vt.vehicle_make, vt.vehicle_model, vit.vehicle_image_url, vit.vehicle_image_priority, vehicle_cover_image from vehicle_table vt join vehicle_image_table vit on vt.vehicle_id = vit.vehicle_id WHERE vt.vehicle_registration = '" . $vehicleRegistration . "' ORDER BY vit.vehicle_cover_image DESC,CASE WHEN vit.vehicle_cover_image THEN vit.vehicle_image_priority  ELSE vit.vehicle_image_priority END ASC;");
        $strSQL->execute();
        return $strSQL->fetchAll(PDO::FETCH_ASSOC);
	}

    public function addVehicle() {
		//Add record to database

        echo "<pre>";
        print_r($_POST);
		echo "</pre>";
		
		$query = $this->db->prepare("INSERT INTO vehicle_table (vehicle_registration, vehicle_make, vehicle_model, vehicle_variant, vehicle_engine_size, vehicle_doors, vehicle_colour, vehicle_year, vehicle_mileage, vehicle_fuel, vehicle_transmission, vehicle_mpg, vehicle_road_tax, vehicle_insurance_group, vehicle_extras) VALUES (:vehicleRegistration, :vehicleMake, :vehicleModel, :vehicleVariant, :vehicleEngineSize, :vehicleDoors, :vehicleColour, :vehicleYear, :vehicleMileage, :vehicleFuel, :vehicleTransmission, :vehicleMPG, :vehicleRoadTax, :vehicleInsuranceGroup, :vehicleExtras)");

		$query->bindParam(":vehicleRegistration", $_POST['vehicleRegistration'], PDO::PARAM_STR);
		$query->bindParam(":vehicleMake", $_POST['vehicleMake'], PDO::PARAM_STR);
		$query->bindParam(":vehicleModel", $_POST['vehicleModel'], PDO::PARAM_STR);
		$query->bindParam(":vehicleVariant", $_POST['vehicleVariant'], PDO::PARAM_STR);
		$query->bindParam(":vehicleEngineSize", $_POST['vehicleEngineSize'], PDO::PARAM_INT);
		$query->bindParam(":vehicleDoors", $_POST['vehicleDoors'], PDO::PARAM_INT);
		$query->bindParam(":vehicleColour", $_POST['vehicleColour'], PDO::PARAM_STR);
		$query->bindParam(":vehicleYear", $_POST['vehicleYear'], PDO::PARAM_INT);
		$query->bindParam(":vehicleMileage", $_POST['vehicleMileage'], PDO::PARAM_INT);
		$query->bindParam(":vehicleFuel", $_POST['vehicleFuel'], PDO::PARAM_STR);
		$query->bindParam(":vehicleTransmission", $_POST['vehicleTransmission'], PDO::PARAM_STR);
		$query->bindParam(":vehicleMPG", $_POST['vehicleMPG'], PDO::PARAM_STR);
		$query->bindParam(":vehicleRoadTax", $_POST['vehicleRoadTax'], PDO::PARAM_STR);
		$query->bindParam(":vehicleInsuranceGroup", $_POST['vehicleInsuranceGroup'], PDO::PARAM_INT);
		$query->bindParam(":vehicleExtras", $_POST['vehicleExtras'], PDO::PARAM_STR);                   

		$query->execute();

		$response = $query->errorInfo();

		if(isset($response[2]) && $response[2] != '') {
			header("Location: " . URL . "/error/addVehicle/" . $response[2]);
		} else {
			if($query->rowCount() > 0) {
				$vehicleID = $this->db->lastInsertId();
				echo "Added " . $_POST['vehicleRegistration'] . " to Database";
				//Upload images
				if( strtolower($_SERVER[ 'REQUEST_METHOD'] ) == 'post' && !empty( $_FILES['vehicleImages'])) {
					$images_root_directory = ROOT_DIR . "/view/asset/images/vehicles/";
					
					$imageCount = sizeof($_FILES["vehicleImages"]['name']);
					
					$fileName = $_POST['vehicleMake'] . "/" . $_POST['vehicleModel'] . "/" . $_POST['vehicleRegistration'];
					
					echo "<pre>";
					print_r($_FILES);
					echo "</pre>";

					for($i = 0; $i < $imageCount; $i++) {
						if($_FILES["vehicleImages"]['name'][$i] != '') {
							//Check file integrity
							$imageCheck = getimagesize($_FILES["vehicleImages"]["tmp_name"][$i]);
							if($imageCheck !== false) {
								$image_directory = $images_root_directory . $fileName;	
			
								//Check directory exists
								if (!file_exists($image_directory)) {
									mkdir($image_directory, 0777, true);
								}
								
								//Check file count
								echo "<pre>" . $image_directory . "</pre>";
								$files = scandir($image_directory);
								$fileCount = count($files)-2;
								echo "<pre>File Count: " . $fileCount . "</pre>";
								$fileCount = ($fileCount + 1);						
			
								$currentTime = time();
								$imageFiles = glob($image_directory. "/" . $_POST['vehicleRegistration'] . "_" . $currentTime . '.*', GLOB_MARK);

								echo "There are " . count($imageFiles) . " images with the filename " . $image_directory. "/" . $_POST['vehicleRegistration'] . "_" . $currentTime . ".*<br />";

								while(count($imageFiles) == 1){
									$currentTime++;
									$imageFiles = glob($image_directory. "/" . $_POST['vehicleRegistration'] . "_" . $currentTime . '.*', GLOB_MARK);
								}

								//Set image name
								$imageFileType = strtolower(pathinfo($_FILES["vehicleImages"]["name"][$i], PATHINFO_EXTENSION));
								switch($imageFileType) {
									case 'jpg':
										$imageName = $_POST['vehicleRegistration'] . "_" . $currentTime . ".jpg";
										break;
									
									case 'png':
										$imageName = $_POST['vehicleRegistration'] . "_" . $currentTime . ".png";
										break;
									
									case 'jpeg':
										$imageName = $_POST['vehicleRegistration'] . "_" . $currentTime . ".jpeg";
										break;
			
									case 'gif':
										$imageName = $_POST['vehicleRegistration'] . "_" . $currentTime . ".gif";
										break;
									
									default:
										$imageName = null;
										echo "File is not in correct format.";
										break;
								}
								
								echo "<pre>" . $imageName . "(" . $_FILES["vehicleImages"]["name"][$i] . ")</pre>";

								//Upload file
								if(!is_null($imageName)) {
									move_uploaded_file($_FILES['vehicleImages']['tmp_name'][$i], $image_directory . "/" . $imageName); 
									
									//Add images to vehicle_image_table
									if(isset($_POST['coverImage']) && $_POST['coverImage'][0] == $i) {
										$coverImage = 1;
									} else {
										$coverImage = 0;
									}
									$imageSalt = base64_encode($fileName . "/" . $imageName);
									$query = $this->db->prepare("INSERT INTO vehicle_image_table (vehicle_id, vehicle_image_url, vehicle_image_priority, vehicle_cover_image, vehicle_image_salt) VALUES (:vehicleID, :vehicleImageURL, :vehicleImagePriority, :vehicleCoverImage, :vehicleImageSalt)");
									$query->bindParam(":vehicleID", $vehicleID, PDO::PARAM_INT);
									$query->bindParam(":vehicleImageURL", $imageName, PDO::PARAM_STR);
									$query->bindParam(":vehicleImagePriority", $fileCount, PDO::PARAM_INT);
									$query->bindParam(":vehicleCoverImage", $coverImage, PDO::PARAM_INT);
									$query->bindParam(":vehicleImageSalt", $imageSalt, PDO::PARAM_INT);

									$query->execute();

									$response = $query->errorInfo();

									if(isset($response[2]) && $response[2] != '') {
										//header("Location: " . URL . "/error/addVehicle/" . $response[2]);
										echo $response[2];
									}
									
									echo "<pre>" . $imageName . "(" . $_FILES["vehicleImages"]['name'][$i] . ") has been uploaded to " . $image_directory . "</pre>";
								}
							} else {
								echo "File is not an image.";
							}		
						}
					}
				}   
			}
		}

		header("location: " . URL . "/admin/vehicles/updateVehicle/" . $_POST['vehicleRegistration']);
		

    }

    public function updateVehicle() {
		
		echo "<pre>";
		print_r($_POST);
		echo "</pre>";
		
     $query = $this->db->prepare("UPDATE vehicle_table SET  
            vehicle_registration = :vehicleRegistration,
            vehicle_make = :vehicleMake,
            vehicle_model = :vehicleModel,
            vehicle_variant = :vehicleVariant,
            vehicle_engine_size = :vehicleEngineSize,
            vehicle_doors = :vehicleDoors,
            vehicle_colour = :vehicleColour,
            vehicle_year = :vehicleYear,
            vehicle_mileage = :vehicleMileage,
            vehicle_fuel = :vehicleFuel,
            vehicle_transmission = :vehicleTransmission,
            vehicle_mpg = :vehicleMPG,
            vehicle_road_tax = :vehicleRoadTax,
            vehicle_insurance_group = :vehicleInsuranceGroup,
            vehicle_extras = :vehicleExtras
            WHERE vehicle_id = :vehicleID");

        $query->bindParam(":vehicleRegistration", $_POST['vehicleRegistration']);
        $query->bindParam(":vehicleMake", $_POST['vehicleMake']);
        $query->bindParam(":vehicleModel", $_POST['vehicleModel']);
        $query->bindParam(":vehicleVariant", $_POST['vehicleVariant']);
        $query->bindParam(":vehicleEngineSize", $_POST['vehicleEngineSize']);
        $query->bindParam(":vehicleDoors", $_POST['vehicleDoors']);
        $query->bindParam(":vehicleColour", $_POST['vehicleColour']);
        $query->bindParam(":vehicleYear", $_POST['vehicleYear']);
        $query->bindParam(":vehicleMileage", $_POST['vehicleMileage']);
        $query->bindParam(":vehicleFuel", $_POST['vehicleFuel']);
        $query->bindParam(":vehicleTransmission", $_POST['vehicleTransmission']);
        $query->bindParam(":vehicleMPG", $_POST['vehicleMPG']);
        $query->bindParam(":vehicleRoadTax", $_POST['vehicleRoadTax']);
        $query->bindParam(":vehicleInsuranceGroup", $_POST['vehicleInsuranceGroup']);
        $query->bindParam(":vehicleExtras", $_POST['vehicleExtras']);                   
        $query->bindParam(":vehicleID", $_POST['vehicleID']);               

        echo "<pre>";
        //print_r($data);
        echo "</pre>";

		$query->execute();


        if($query->rowCount() > 0) {
            header("location: " . URL . "/admin/vehicles/updateVehicle/" . $_POST['vehicleRegistration']);
        };

    }
	
	public function addVehicleImage() {
		echo "<pre>";
		print_r($_POST);
		print_r($_FILES);
		echo "</pre>";
		
		$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

		$uri_array = explode('/', $uri);
		
		$vehicleRegistration = end($uri_array);
		
		$query = $this->db->prepare("SELECT vehicle_id, vehicle_registration, vehicle_make, vehicle_model FROM vehicle_table WHERE vehicle_registration = :vehicleRegistration");

		$query->bindParam(":vehicleRegistration", $vehicleRegistration, PDO::PARAM_STR);
		
		$query->execute();

        if($query->rowCount() > 0) {
			$result = $query->fetchAll(PDO::FETCH_ASSOC);
			
			foreach($result as $vehicle) {
				echo "Updating images for " . $vehicle['vehicle_registration'] . "<br />";
				
				if( strtolower($_SERVER[ 'REQUEST_METHOD'] ) == 'post' && !empty( $_FILES['vehicleImages'])) {
					$images_root_directory = ROOT_DIR . "/view/asset/images/vehicles/";
					
					$imageCount = sizeof($_FILES["vehicleImages"]['name']);
					
					$fileName = $vehicle['vehicle_make'] . "/" . $vehicle['vehicle_model'] . "/" . $vehicle['vehicle_registration'];
					
					echo "<pre>";
					print_r($_FILES);
					echo "</pre>";

					for($i = 0; $i < $imageCount; $i++) {
						if($_FILES["vehicleImages"]['name'][$i] != '') {
							//Check file integrity
							$imageCheck = getimagesize($_FILES["vehicleImages"]["tmp_name"][$i]);
							if($imageCheck !== false) {
								$image_directory = $images_root_directory . $fileName;	
			
								//Check directory exists
								if (!file_exists($image_directory)) {
									mkdir($image_directory, 0777, true);
								}
								
								//Check file count
								echo "<pre>" . $image_directory . "</pre>";
								$files = scandir($image_directory);
								$fileCount = count($files)-2;
								echo "<pre>File Count: " . $fileCount . "</pre>";
								$fileCount = ($fileCount + 1);							
			
								
								$currentTime = time();
								$imageFiles = glob($image_directory. "/" . $vehicle['vehicle_registration'] . "_" . $currentTime . '.*', GLOB_MARK);
								while(count($imageFiles) == 1){
									echo $image_directory. "/" . $vehicle['vehicle_registration'] . "_" . $currentTime . " - exists";
									$currentTime++;
									$imageFiles = glob($image_directory. "/" . $vehicle['vehicle_registration'] . "_" . $currentTime . '.*', GLOB_MARK);
								}

								//Set image name
								$imageFileType = strtolower(pathinfo($_FILES["vehicleImages"]["name"][$i], PATHINFO_EXTENSION));
								switch($imageFileType) {
									case 'jpg':
										$imageName = $vehicle['vehicle_registration'] . "_" . $currentTime . ".jpg";
										break;
									
									case 'png':
										$imageName = $vehicle['vehicle_registration'] . "_" . $currentTime . ".png";
										break;
									
									case 'jpeg':
										$imageName = $vehicle['vehicle_registration'] . "_" . $currentTime . ".jpeg";
										break;
			
									case 'gif':
										$imageName = $vehicle['vehicle_registration'] . "_" . $currentTime . ".gif";
										break;
									
									default:
										$imageName = null;
										echo "File is not in correct format.";
										break;
								}
								
								echo "<pre>" . $imageName . "(" . $_FILES["vehicleImages"]["name"][$i] . ")</pre>";

								//Upload file
								if(!is_null($imageName)) {
									move_uploaded_file($_FILES['vehicleImages']['tmp_name'][$i], $image_directory . "/" . $imageName); 
									
									//Add images to vehicle_image_table
									if(isset($_POST['coverImage']) && $_POST['coverImage'][0] == $i) {
										$coverImage = 1;
										$query = $this->db->prepare("UPDATE vehicle_image_table SET vehicle_cover_image = 0 WHERE vehicle_id = :vehicleID");
										$query->bindParam(":vehicleID", $vehicle['vehicle_id'], PDO::PARAM_INT);
										$query->execute();
										
									} else {
										$coverImage = 0;
									}

									$imageSalt = base64_encode($fileName . "/" . $imageName);
									$query = $this->db->prepare("INSERT INTO vehicle_image_table (vehicle_id, vehicle_image_url, vehicle_image_priority, vehicle_cover_image, vehicle_image_salt) VALUES (:vehicleID, :vehicleImageURL, :vehicleImagePriority, :vehicleCoverImage, :vehicleImageSalt)");
									$query->bindParam(":vehicleID", $vehicle['vehicle_id'], PDO::PARAM_INT);
									$query->bindParam(":vehicleImageURL", $imageName, PDO::PARAM_STR);
									$query->bindParam(":vehicleImagePriority", $fileCount, PDO::PARAM_INT);
									$query->bindParam(":vehicleCoverImage", $coverImage, PDO::PARAM_INT);
									$query->bindParam(":vehicleImageSalt", $imageSalt, PDO::PARAM_INT);

									$query->execute();

									$response = $query->errorInfo();

									if(isset($response[2]) && $response[2] != '') {
										header("Location: " . URL . "/error/addVehicle/" . $response[2]);
									}
									
									echo "<pre>" . $imageName . "(" . $_FILES["vehicleImages"]['name'][$i] . ") has been uploaded to " . $image_directory . "</pre>";
								}
							} else {
								echo "File is not an image.";
							}		
						}
					}	
				}
			}
			header("location: " . URL . "/admin/vehicles/updateVehicle/" . $vehicle['vehicle_registration']);
        };			
	}
	
	public function shareVehicle($vehicleRegistration) {
		$query = $this->db->prepare("SELECT vehicle_registration, vehicle_make, vehicle_model, vehicle_variant, vehicle_colour, vehicle_fuel, vehicle_mileage from vehicle_table where vehicle_registration = :vehicleRegistration");
		$query->bindParam(":vehicleRegistration", $vehicleRegistration);
        $query->execute();
		
		$result = $query->fetchAll(PDO::FETCH_ASSOC);

		if($query->rowCount() == 1) {
			echo "foo";
			foreach($result as $vehicle) {
				echo "foo";
				define('FACEBOOK_SDK_V4_SRC_DIR', ROOT_DIR . '/lib/facebook/');
				require_once(ROOT_DIR . '/lib/facebook/autoload.php');

				$fb = new Facebook\Facebook([
					'app_id' => '404345163435245',
					'app_secret' => '5f7d800bd6b7723e6125acf7566aa6d8',
					'default_graph_version' => 'v2.2',
				   ]);


				//Post property to Facebook
				$linkData = [
					'link' => 'www.gorbulas.co.uk/projects/jf_cars/showroom/' . $vehicle['vehicle_make'] . '/' . $vehicle['vehicle_model'] . '/' . $vehicle['vehicle_registration'] . '',
					'message' => 'Now in stock: ' . chr(10) . '' . chr(10) . '' . $vehicle['vehicle_make'] . ' ' . $vehicle['vehicle_model'] . ' ' . $vehicle['vehicle_variant'] . '  ' . chr(10) . '' . chr(10) . 'Colour: ' . $vehicle['vehicle_colour'] . '' . chr(10) . '' . chr(10) . 'Fuel Type: ' . $vehicle['vehicle_fuel'] . ' ' . chr(10) . '' . chr(10) . 'Mileage: ' . $vehicle['vehicle_mileage'] . ''
				   ];
				   
				   print_r($linkData);
				   
				   $pageAccessToken ='EAAFvvZCHVTO0BAHaNZA0ipZBRGvNAZB3mX0nlFu4rBeL1kzyldxeEoO8AMZAIIaJdX7sr7GDAYhVsbcHZAz1DbgCULe6PT9QIhlgcSKC2hAmxvV2tSmn3ucBn05SwbZBYEeb75bXmmNEESNxEz2xc8Lsur9KZCYqaSKqulxZAXRnIK8Q7XHgstfVQrWdyAwVZBoqBZAlVESibVZBHQZDZD';
				   
				   try {
					$response = $fb->post('/me/feed', $linkData, $pageAccessToken);
					header("Location: " . URL . "/admin/vehicles");
				   } catch(Facebook\Exceptions\FacebookResponseException $e) {
					echo 'Graph returned an error: '.$e->getMessage();
					exit;
				   } catch(Facebook\Exceptions\FacebookSDKException $e) {
					echo 'Facebook SDK returned an error: '.$e->getMessage();
					exit;
				   }
				   $graphNode = $response->getGraphNode();
			}
		}
        
	}

	public function removeVehicle($vehicleRegistration) {
		echo "Remove Vehicle " . $vehicleRegistration . "<br />";
		//Get vehicle_id, vehicle_make, vehicle_model, vehicle_registration
		$query = $this->db->prepare("SELECT vehicle_id, vehicle_make, vehicle_model, vehicle_registration FROM vehicle_table WHERE vehicle_registration = :vehicleRegistration");
		$query->bindParam(":vehicleRegistration", $vehicleRegistration,  PDO::PARAM_STR);
		$query->execute();

		echo "Found: " . $query->rowCount() . "<br />";

		if($query->rowCount() > 0) {
			$result = $query->fetchAll(PDO::FETCH_ASSOC);

			foreach($result as $vehicle) {
				$vehicleImageDirectory =  ROOT_DIR . "/view/asset/images/vehicles/" . $vehicle['vehicle_make'] . "/" . $vehicle['vehicle_model'] . "/" . $vehicle['vehicle_registration'] . "/";
				$modelDirectory = ROOT_DIR . "/view/asset/images/vehicles/" . $vehicle['vehicle_make'] . "/" . $vehicle['vehicle_model'] . "/";
				$makeDirectory = ROOT_DIR . "/view/asset/images/vehicles/" . $vehicle['vehicle_make'] . "/";
				echo "Image Directory : " . $vehicleImageDirectory . "<br />";
				

				echo "<pre>";
				print_r($imageFiles);
				echo "</pre>";

				//Remove all images from directory
				$imageFiles = glob($vehicleImageDirectory . '*', GLOB_MARK);
				foreach ($imageFiles as $file) {
					if (!is_dir($file)) {
						echo "Delete File " . $file . "<br />";
						unlink($file);
					}
				}
				//Directory will now be empty - remove it
				rmdir($vehicleImageDirectory);

				//Check if Model directory is empty and remove
				$modelFiles = glob($modelDirectory . '*', GLOB_MARK);
				if(count($modelFiles) == 0) {
					rmdir($modelDirectory);
					$makeFiles = glob($makeDirectory . '*', GLOB_MARK);
					if(count($makeFiles) == 0) {
						rmdir($makeDirectory);
					}
				}

				
				

				$query = $this->db->prepare("DELETE FROM vehicle_table WHERE vehicle_id = :vehicleID");
				$query->bindParam(":vehicleID", $vehicle['vehicle_id'],  PDO::PARAM_INT);
				$query->execute();

				$query = $this->db->prepare("DELETE FROM vehicle_image_table WHERE vehicle_id = :vehicleID");
				$query->bindParam(":vehicleID", $vehicle['vehicle_id'],  PDO::PARAM_INT);
				$query->execute();

				$query = $this->db->prepare("DELETE FROM sale_table WHERE vehicle_id = :vehicleID");
				$query->bindParam(":vehicleID", $vehicle['vehicle_id'],  PDO::PARAM_INT);
				$query->execute();

				header("Location: " . URL . "/admin/vehicles");

			}
		} else {
			header("Location: " . URL . "/admin/vehicles");
		}
	}

	public function removeImage($imageSalt) {
		$query = $this->db->prepare("SELECT vehicle_image_id, vehicle_id FROM vehicle_image_table WHERE vehicle_image_salt = :imageSalt");
		$query->bindParam(":imageSalt", $imageSalt,  PDO::PARAM_STR);
		$query->execute();

		if($query->rowCount() == 1) {
			$imageFile = ROOT_DIR . "/view/asset/images/vehicles/" . base64_decode($imageSalt);
			if(!is_dir($imageFile)) {
				unlink($imageFile);

				$query = $this->db->prepare("DELETE FROM vehicle_image_table WHERE vehicle_image_salt = :imageSalt");
				$query->bindParam(":imageSalt", $imageSalt,  PDO::PARAM_STR);
				$query->execute();

				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function getData() {
        $json = file_get_contents("https://uk1.ukvehicledata.co.uk/api/datapackage/VehicleData?v=2&api_nullitems=1&auth_apikey=a6105897-35ac-4af3-9d70-fbb4568c5839&key_VRM=km12akk");
        $obj = json_decode($json);
		echo "<pre>";
		print_r($obj);
		echo "</pre>";

		echo "<pre>";
		echo "Basic/Search fields<br />";
		echo "Make: " . $obj->Response->DataItems->SmmtDetails->Marque . "<br />";
		echo "Model: " . $obj->Response->DataItems->SmmtDetails->Range . "<br />";
		echo "Fuel Type: " . $obj->Response->DataItems->SmmtDetails->FuelType . "<br />";
		echo "Transmission " . $obj->Response->DataItems->SmmtDetails->Transmission . "<br />";
		echo "Engine Size: " . $obj->Response->DataItems->SmmtDetails->EngineCapacity . "<br />";
		echo "Doors: " . $obj->Response->DataItems->SmmtDetails->EngineCapacity . "<br />";
		echo "Year: " . $obj->Response->DataItems->VehicleRegistration->DateFirstRegistered . "<br />";
		echo "</pre>";

		echo "<pre>";
		echo "Additional fields<br />";
		echo "Variant: " . $obj->Response->DataItems->SmmtDetails->ModelVariant . "<br />";
		echo "Body Style: " . $obj->Response->DataItems->SmmtDetails->BodyStyle . "<br />";
		echo "Gears, Body Style, Colour, Number of previous owners, Tax Band + 6/12 cost, Fuel Consumption, BHP, Torque, Acceleration";
		echo "</pre>";
    }

}