#Event list:

All events except __event.driver.login__ are required authorization. Authorization done by sending header `X-API-Authorization` with authorization token.

####event.order.update
* orderId - Order ID
* directionId - Direction ID;

####event.log.location
* provider - provider (GPS or NETWORK);
* latitude - longitude;
* longitude - latitude;
* speed - viacle speed in km/h; 
* device - object about device status;
    * plugged - is charged is plugged;
    * volume - current volume of device (percentages);
    * brightness - screen brightness;
    * locked - is device is locked;
    * battery - object about device battery status
        * scale - battery scale;
        * level - battery level;
        * temp - battery temperature;
        * health - battery health status;
####event.driver.emergency
* date - Event date;
####event.app.update
* code - current application code version;
* version - current application version name;
####event.driver.stop
\-
####event.driver.start
\-
####event.driver.pong
* ts - unix timestamp of last update
####event.driver.login
* device - object down bellow
    * version - current application version name;
    * code - current application code version;
* imei - device imei code;
####event.log.call
* collection - _array of objects with structure down bellow_
    * action - action of event;
    * number - phone number of receiver or caller;
    * eventStart - event start date;
    * eventEnd - event ent time (can be null);
####event.driver.history
  * date - selected date;
