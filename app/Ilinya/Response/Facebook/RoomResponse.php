<?php
namespace App\Ilinya\Response\Facebook;

/*
    @Providers
*/
use App\Ilinya\Webhook\Facebook\Messaging;
use App\Ilinya\User;
use App\Ilinya\Bot;
use Illuminate\Http\Request;
use App\Ilinya\Tracker;
use App\Ilinya\Http\Curl;
/*
    @Template
*/
use App\Ilinya\Templates\Facebook\QuickReplyTemplate;
use App\Ilinya\Templates\Facebook\ButtonTemplate;
use App\Ilinya\Templates\Facebook\GenericTemplate;
use App\Ilinya\Templates\Facebook\LocationTemplate;
use App\Ilinya\Templates\Facebook\ListTemplate;

/*
    @Elements
*/

use App\Ilinya\Templates\Facebook\ButtonElement;
use App\Ilinya\Templates\Facebook\GenericElement;
use App\Ilinya\Templates\Facebook\QuickReplyElement;


/*
    @API
*/
use App\Ilinya\API\Controller;
use App\Ilinya\API\SheetController;

/**
 * @STORAGE 
 */
use Storage;


class RoomResponse{

  protected $messaging;
  protected $tracker;
  protected $bot; 
  private $curl;
  private $user;
  public function __construct(Messaging $messaging){
      $this->messaging = $messaging;
      $this->tracker   = new Tracker($messaging);
      $this->bot       = new Bot($messaging);
      $this->curl = new Curl();
  }
  
  public function user(){
    $user = $this->curl->getUser($this->messaging->getSenderId());
    $this->user = new User($this->messaging->getSenderId(), $user['first_name'], $user['last_name']);
  }
// Start Yol
  public function roomMenuStart()
  {
    $this->user();;
    $title =  "Greetings from Mezzo! I'm currently away right now. I'll get back to you in a bit ".$this->user->getFirstName()." !\n\nYou can also call our Reservations at 0906 423 1579 for booking inquiries and room availability.\n\n#anewdimensionofluxury";    
    return ["text" => $title];
  }
  public function roomMenu(){
    $title ="How can we help you with your room inquiry?";
    $menus= array( 
      array("payload"=> "@pRoomMenuSelected", "title"=>"ROOM RATES" ,"web"=>false),
      array("url"=> "https://mezzohotel.com", "title"=>"HOTEL FACILITIES" ,"web"=>true),
      array("payload"=> "@pRoomMenuSelected", "title"=>"ROOM RESERVATIONS","web"=>false)
    );
    // 
    foreach ($menus as $menu) {
      if (!$menu['web']) {
        $buttons[] = ButtonElement::title($menu["title"])
                  ->type('postback')
                  ->payload($menu["payload"])
                  ->toArray();
      } else {
        $buttons[] = ButtonElement::title($menu["title"])
                  ->type('web_url')
                  ->url($menu["url"])
                  ->toArray();
      }
    }
    $response = ButtonTemplate::toArray($title,$buttons);
    return $response;
  }
  public function rooms($isRreserve){
    $credentials = array("5","6");
    $categories = SheetController::getSheetContent($credentials); 
    $buttons = [];
    $elements = [];
    if(sizeof($categories)>0){
        $prev = $categories[0]['title'];
        $i = 0; 
        foreach ($categories as $category) {
             $subtitle = $category['price'];
             $payload= preg_replace('/\s+/', '_', strtolower($category['title']));
             $imgArray= explode(',' , $category['images = array']);
             $imageUrl = "https://mezzohotel.com/img/".$imgArray[0];
             if ($isRreserve!=true) {
              $buttons[] = ButtonElement::title('BOOK NOW')
              ->type('web_url')
              ->url("https://mezzohotel.com/managebooking.php")
              ->toArray();
             } else {
              $buttons[] = ButtonElement::title('RESERVE')
              ->type('postback')
              ->payload($payload."@pRoomInquiry")
              ->toArray();
             } 
            if($i < sizeof($categories) - 1){
                if($prev != $categories[$i + 1]['title']){
                    $title = $category['title'];
                    $elements[] = GenericElement::title($title)
                        ->imageUrl($imageUrl)
                        ->subtitle($subtitle)
                        ->buttons($buttons)
                        ->toArray();
                    $prev = $category['title'];
                    $buttons = null;
                    echo $imageUrl.'<br />';
                }
            }
            else{
                $title = $category['title'];
                $elements[] = GenericElement::title($title)
                    ->imageUrl($imageUrl)
                    ->subtitle($subtitle)
                    ->buttons($buttons)
                    ->toArray();
                    echo $imageUrl.'<br />';
            }
            
            $i++;
        }
    }
    $response =  GenericTemplate::toArray($elements);
    Storage::put('Rooms.json', json_encode($response));
    return $response;
}
  public function concerns($parameter){
    $title = $parameter;
    $elements[] = GenericElement::title($title)
                        ->imageUrl(null)
                        ->subtitle("test")
                        ->buttons(null)
                        ->toArray();
    $response =  GenericTemplate::toArray($elements);
    Storage::put('Packages.json', json_encode($response));
    return $response;
  }

  //END

}

