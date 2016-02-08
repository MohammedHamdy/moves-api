<?php
    
    //fetch table rows from mysql db
	function get_activity($found){
		
//open connection to mysql db
    $connection = mysqli_connect("localhost","root","","task") or die("Error " . mysqli_error($connection));
    $sql = "select * from activities where userid=$found";
    $result = mysqli_query($connection, $sql) or die("Error in Selecting " . mysqli_error($connection));
    //create an array
    $emparray = array();
    while($row =mysqli_fetch_assoc($result))
    {
        $emparray[] = $row;
		
    }
	mysqli_close($connection);
	return $emparray;
	break;
    //echo json_encode($emparray);
   
	}
    //close the db connection*/
	
    
?>