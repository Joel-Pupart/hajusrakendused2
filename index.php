<?php
    $config = require_once 'config.php';

    function connection ($config) {
        $dsn = "mysql:host=" . $config['host'] . ";dbname=" . $config['database'];

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
             return new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (\PDOException $e) {
             throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    function add($lat, $lon, $name, $desc, $db) {
      $sql = 'INSERT INTO markers (latitude, longitude, name, description) VALUES ("' . $lat . '","' . $lon . '","' . $name . '","' . $desc . '");';
      
      try {
        $db->prepare($sql)->execute();
      } catch (PDOException $e) {
        echo $e->getMessage();
      }

    }

    function delete($id, $db) {
      $sql = 'DELETE FROM markers WHERE id = "' . $id . '";';

      try {
        $db->prepare($sql)->execute();
      } catch (PDOException $e) {
        echo $e->getMessage();
      }

    }

    function edit($id, $lat, $lon, $name, $desc, $db) {
      $sql = 'UPDATE markers SET latitude = "'. $lat . '", longitude = "' . $lon . '", name = "' . $name . '", description = "' . $desc . '" WHERE id = "' . $id .'";';
      
      try {
        $db->prepare($sql)->execute();
      } catch (PDOException $e) {
        echo $e->getMessage();
      }
    
    }


    $db = connection($config);
    $sql = 'SELECT * FROM markers';
    $markers = $db->query($sql)->fetchAll();

    
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    $latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
    $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $edit = filter_input(INPUT_POST, 'edit', FILTER_SANITIZE_STRING);
    $delete = filter_input(INPUT_POST, 'delete', FILTER_SANITIZE_STRING);
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
    
    if (isset($action) && isset($latitude) && isset($longitude) && isset($name) && isset($description)) {
      add($latitude, $longitude, $name, $description, $db);
      header('Location: '.$_SERVER['REQUEST_URI']);
    }

    if (isset($edit)) {
      edit($id, $latitude, $longitude, $name, $description, $db);
      header('Location: '.$_SERVER['REQUEST_URI']);
    }

    if (isset($delete)) {
      delete($id, $db);
      header('Location: '.$_SERVER['REQUEST_URI']);
    }

    


?>


<!DOCTYPE html>
<html>
  <head>
    <title>Simple Map</title>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <script
      src="https://maps.googleapis.com/maps/api/js?key=&callback=initMap&libraries=&v=weekly"
      defer
    ></script>
    <style type="text/css">
      /* Always set the map height explicitly to define the size of the div
       * element that contains the map. */
      #map {
        height: 100%;
      }

      /* Optional: Makes the sample page fill the window. */
      html,
      body {
        height: 100%;
        margin: 0;
        padding: 0;
      }
    </style>
    <script>
      let map;

      let markers = [<?php 
            foreach ($markers as $index => $row) {
              if ($index === array_key_last($markers)){
                echo "[" . $row->id . "," . $row->latitude . "," . $row->longitude . ",'" . $row->name . "','" . $row->description . "']";
              } else {
                echo "[" . $row->id . "," . $row->latitude . "," . $row->longitude . ",'" . $row->name . "','" . $row->description . "'],";
              }
            }?>];

      function initMap() {

        

        //creating the map
        map = new google.maps.Map(document.getElementById("map"), {
          center: { lat: 58, lng: 22 },
          zoom: 8,
        });

        markers.forEach(item => {
          let marker = new google.maps.Marker({
            position: {lat: item[1], lng: item[2]},
            animation: google.maps.Animation.DROP,
            draggable: true,
            map:map,
          })

          let infowindow = new google.maps.InfoWindow({
            content: 
              "<form method='post'>\
                  <div>Latitude: " + item[1] + "</div>\
                  <div>Longitude: " + item[2] + "</div>\
                  <input type='hidden' name='latitude' value=" + item[1] + ">\
                  <input type='hidden' name='longitude' value=" + item[2] + ">\
                  <input type='hidden' name='id' value=" + item[0] + ">\
                  <div>\
                    <label for='name'>Name</label>\
                    <input type=text name='name' value='" + item[3] + "'>\
                  </div>\
                  <div>\
                    <label for='description'>Description</label>\
                    <input type=text name='description' value='" + item[4] +"'>\
                  <div>\
                  <button type='submit' name='edit'>Edit</button>\
                  <button type='submit' name='delete'>Delete</button>\
                </form>\
              "
          });
          marker.addListener("click", () => {
            infowindow.open(map, marker);
          });

          marker.addListener("dragend", function(event) {
            infowindow.setContent(  
                "<form method='post'>\
                    <div>Latitude: " + event.latLng.lat().toFixed(4)  + "</div>\
                    <div>Longitude: " + event.latLng.lng().toFixed(4) + "</div>\
                    <input type='hidden' name='latitude' value=" + event.latLng.lat() + ">\
                    <input type='hidden' name='longitude' value=" + event.latLng.lng() + ">\
                    <input type='hidden' name='id' value=" + item[0] + ">\
                    <div>\
                      <label for='name'>Name</label>\
                      <input type=text name='name' value='" + item[3] + "'>\
                    </div>\
                    <div>\
                      <label for='description'>Description</label>\
                      <input type=text name='description' value='" + item[4] +"'>\
                    <div>\
                    <button type='submit' name='edit'>Edit</button>\
                    <button type='submit' name='delete'>Delete</button>\
                  </form>\
                ");
            
          });
        });

        //sending click coordinates to the addMarker() function
        google.maps.event.addListener(map, "click", function(event) {
          addMarker(event.latLng);
        });

        function addMarker(data) {

          //creating a new marker
          let marker = new google.maps.Marker({
            position: data,
            animation: google.maps.Animation.DROP,
            map:map,
          });

          //creating an infowindow with a form for the marker
          let infowindow = new google.maps.InfoWindow({
            content: 
              "<form method='post'>\
                  <div>Latitude: " + data.lat().toFixed(4)  + "</div>\
                  <div>Longitude: " + data.lng().toFixed(4)  + "</div>\
                  <input type='hidden' name='latitude' value=" + data.lat() +">\
                  <input type='hidden' name='longitude' value=" + data.lng() +">\
                  <div>\
                    <label for='name'>Name</label>\
                    <input type=text name='name'>\
                  </div>\
                  <div>\
                    <label for='description'>Description</label>\
                    <input type=text name='description'>\
                  <div>\
                  <button type='submit' name='action'>Save</button>\
                </form>\
              "
          });
         
          //opening the infowindow immediatly
          infowindow.open(map, marker);

          //closing the infowindow will delete the marker
          infowindow.addListener("closeclick", () => {
            marker.setMap(null);
          });

          //clicking anywhere else on the map will also delete the marker
          map.addListener("click", () => {
            marker.setMap(null);
          })

          /*marker.addListener("click", () => {
            infowindow.open(map, marker);
          });*/

        };
      }
      
    </script>
  </head>
  <body>
    <div id="map"></div>
  </body>
</html>
