<?php
  
class Feed {
  private static $fetched = array();
  private static $messages = array();
  private static $message_id;
  private static $colors = array();
  private $message_file = 'include/msg.dat';
  
  function create_cache() {
    $this->fetch_messages();
    file_put_contents($this->message_file, serialize(self::$fetched));
  }
  
  function read_cache() {
    if (file_exists($this->message_file))
      self::$fetched = unserialize(file_get_contents($this->message_file));
    else
      $this->create_cache();
  }
  
  public function fetch($refetch) {
    if (!self::$messages) {
      if ($refetch) {
        if (file_exists($this->message_file) && time() - filemtime($this->message_file) < $GLOBALS['update_interval'] * 60)
          $this->read_cache();
        else
          $this->create_cache();
      } else
        $this->read_cache();
    }
  }
  
  public function render($entries) {
    $this->fetch(false);
    $this->process_messages();
    return $this->display_messages($entries);
  }
  
  function fetch_github($username, $password) {
    $events = array();
    $i = 1;
    $response = true;
    while ($response !== array()) {
      $ch = curl_init("https://api.github.com/users/$username/events/public?page=$i");
      curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("User-Agent: $username"));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      $response = json_decode(curl_exec($ch));
      curl_close($ch);
      $events = array_merge($events, $response);
      $i++;
    }
    return $events;
  }
  
  function fetch_youtube($username) {
    return file_get_contents("http://gdata.youtube.com/feeds/base/users/$username/uploads?alt=rss&v=2");
  }
  
  function fetch_googleplay($app) {
    $response = file_get_contents("https://androidquery.appspot.com/api/market?app=$app");
    return json_decode($response);
  }
  
  function process_github($events) {
    foreach ($events as $event) {
      $repo = str_replace("{$event->actor->login}/", '', $event->repo->name);
      $message = array(
        'timestamp' => strtotime($event->created_at),
        'service' => 'GitHub',
        'serviceImg' => '/assets/github.png',
        'buttonLabel' => 'Details'
      );
      switch ($event->type) {
      case 'PushEvent':
        foreach ($event->payload->commits as $commit) {
          $message['url'] = "https://github.com/{$event->repo->name}/commit/$commit->sha";
          $message['message'] = str_replace("\n", "<br />\n", $commit->message);
          switch (Renderer::$language) {
          case 'en':
            $message['subject'] = "Updated <strong>$repo</strong>";
            break;
          case 'de':
            $message['subject'] = "<strong>$repo</strong> aktualisiert";
            break;
          }
        }
        $messages[] = $message;
        break;
      case 'CreateEvent':
        if ($event->payload->ref_type == 'repository') {
          $message['url'] = "https://github.com/{$event->repo->name}";
          $message['message'] = $event->payload->description;
          switch (Renderer::$language) {
          case 'en':
            $message['subject'] = "Created <strong>$repo</strong>";
            break;
          case 'de':
            $message['subject'] = "<strong>$repo</strong> erstellt";
            break;
          }
          $messages[] = $message;
        }
        break;
      case 'ForkEvent':
        $message['url'] = $event->payload->forkee->html_url;
        $message['message'] = $event->payload->forkee->description;
        switch (Renderer::$language) {
        case 'en':
          $message['subject'] = "Forked <strong>{$event->payload->forkee->name}</strong>";
          break;
        case 'de':
          $message['subject'] = "<strong>{$event->payload->forkee->name}</strong> geforkt";
          break;
        }
        $messages[] = $message;
        break;
      case 'WatchEvent': case 'DeleteEvent': case 'GollumEvent': case 'IssuesEvent': case 'IssueCommentEvent':
        break;
      default:
        $message['url'] = $message['message'] = '';
        $message['subject'] = "<em>$event->type</em>";
        $messages[] = $message;
      }
    }
    return $messages;
  }
  
  function process_youtube($xml) {
    $xml = new SimpleXMLElement($xml);
    foreach($xml->channel->item as $item) {
      $message = array(
        'timestamp' => strtotime($item->pubDate),
        'service' => 'YouTube',
        'serviceImg' => '/assets/youtube.png',
        'url' => $item->link,
        'message' => $item->title
      );
      switch (Renderer::$language) {
      case 'en':
        $message['buttonLabel'] = 'Play';
        $message['subject'] = 'Uploaded <strong>video</strong>';
        break;
      case 'de':
        $message['buttonLabel'] = 'Abspielen';
        $message['subject'] = '<strong>Video</strong> hochgeladen';
        break;
      }
      $messages[] = $message;
    }
    return $messages;
  }
  
  function process_googleplay($app) {
    $date = str_replace(Renderer::$months_de, Renderer::$months_en, $app->published);
    $message = array(
      'timestamp' => strtotime($date),
      'service' => 'Google Play',
      'serviceImg' => '/assets/googleplay.png',
      'url' => "https://play.google.com/store/apps/details?id=$app->app&hl=" . Renderer::$language,
      'message' => explode('<br/>', $app->dialog->wbody)[2]
    );
    switch (Renderer::$language) {
    case 'en':
      $message['buttonLabel'] = 'Go to App';
      $message['subject'] = "Updated <strong>$app->name</strong>";
      break;
    case 'de':
    $message['buttonLabel'] = 'Zur App';
    $message['subject'] = "<strong>$app->name</strong> aktualisiert";
      break;
    }
    $messages[] = $message;
    return $messages;
  }
  
  function walk_fetch_googleplay($app) {
    self::$fetched['googleplay'][$app] = $this->fetch_googleplay($app);
  }
  
  function fetch_googleplay_multiple($apps) {
    array_walk($apps, array($this, 'walk_fetch_googleplay'));
  }
  
  function walk_process_googleplay($app) {
    self::$messages = array_merge(self::$messages, self::process_googleplay(self::$fetched['googleplay'][$app]));
  }
  
  function process_googleplay_multiple($apps) {
    array_walk($apps, array($this, 'walk_process_googleplay'));
  }
  
  function fetch_messages() {
    self::$fetched = array(
      'github' => $this->fetch_github($GLOBALS['github_username'], $GLOBALS['github_password']),
      'youtube' => $this->fetch_youtube($GLOBALS['youtube_username']),
      'googleplay' => array(),
    );
    $this->fetch_googleplay_multiple($GLOBALS['googleplay_apps']);
  }
  
  function sort_messages($a, $b) {
      if ($a == $b)
          return 0;
      return ($a['timestamp'] > $b['timestamp']) ? -1 : 1;
  }
  
  /* home.de.html / home.en.html:
    <li>
      <a href="http://youtube.com/ekuiter"><img src="/assets/youtube.png" alt="YouTube" /></a>
      <br />YouTube
    </li>
    feed.de.html / feed.en.html:
    <li>
      <a href="/index.php?p=feed&amp;only=youtube">
        <img src="/assets/youtube.png" alt="YouTube" width="24" />
        Nur YouTube
      </a>
    </li>
  */
  function process_messages() {
    if (!isset($_GET['only']) || $_GET['only'] == 'github')
      $github = $this->process_github(self::$fetched['github']);
    else
      $github = array();
    if (!isset($_GET['only']) || $_GET['only'] == 'youtube')
      $youtube = $this->process_youtube(self::$fetched['youtube']);
    else
      $youtube = array();
    
    self::$messages = array_merge($github/*, $youtube*/);
    
    if (!isset($_GET['only']) || $_GET['only'] == 'googleplay')
      $this->process_googleplay_multiple($GLOBALS['googleplay_apps']);
    
    usort(self::$messages, array($this, 'sort_messages'));
  }
  
  function color($string) {
      mt_srand((double) microtime() * 1000000);
      if (isset(self::$colors[$string]))
        return self::$colors[$string];
      else {
        $color = '';
        while(strlen($color) < 6)
            $color .= sprintf("%02X", mt_rand(40, 180));
        return self::$colors[$string] = $color;
      }
  }
  
  function display_messages($entries) {
    $count = count(self::$messages);
    $half = (int) ($count / 2) + 1;
    if ($entries == '1st-half')
      $collection = array_slice(self::$messages, 0, $half);
    else if ($entries == '2nd-half')
      $collection = array_slice(self::$messages, $half, $count - $half);
    else
      $collection = array_slice(self::$messages, 0, $entries);
    $output = '<table class="feed">';
    for ($idx = 0; $idx < count($collection); $idx++) {
      $message = $collection[$idx];
      $i = self::$message_id;
      $date = Renderer::format_date($message['timestamp']);
      $group_prev = isset($collection[$idx - 1]) && $collection[$idx - 1]['subject'] == $message['subject'] ? 'padding-top:2px;'    : '';
      $group_next = isset($collection[$idx + 1]) && $collection[$idx + 1]['subject'] == $message['subject'] ? 'padding-bottom:2px;' : '';
      $button_pos = $group_next ? 'bottom:7px' : 'bottom:0';
      $random = $group_prev ? $random : $this->color($message['subject']);
      $output .= <<<code
        <tr class="entry" onclick="$('#message$i').slideToggle(200)">
          <td style="width:40px;$group_next$group_prev"><img src="$message[serviceImg]" alt="$message[service]" title="$message[service]" width="24" /></td>
          <td style="width:90px;font-size:0.7em;$group_next$group_prev"><div style="padding-top:7px">$date</div></td>
          <td style="$group_next$group_prev">
            <div style="padding-top:3px">$message[subject]</div>
            <div id="message$i" style="position:relative;display:none;margin-top:7px;font-size:0.8em;height:60px">
              <div style="position:absolute;right:0;$button_pos;width:100px;height:30px">
                <a href="$message[url]" class="button secondary" style="padding-left:3px;padding-right:3px">$message[buttonLabel] &gt;&gt;</a>
              </div>
              <div style="">$message[message]</div>
            </div>
          </td>
          <td style="padding:0;width:10px;background-color:#$random"></td>
        </tr>
code;
      self::$message_id++;
    }
    return "$output</table>";
  }
}
  
?>