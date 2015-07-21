<?php

class urlreveal extends module
{

    public $title = "urlreveal";
    public $author = "g2x3k";
    public $version = "0.98";
    public $date = "20-07-15 23:05";
    private $delay = 0;

    public function init()
    {
        // Channels To ignore
        $this->conf["ignore"]["chans"][] = "#addpre";
        $this->conf["ignore"]["chans"][] = "#addpre.info";
        $this->conf["ignore"]["chans"][] = "#addpre.ftp";
        $this->conf["ignore"]["chans"][] = "#addpre.ext2";
        $this->conf["ignore"]["chans"][] = "#addpre.ext";
        $this->conf["ignore"]["chans"][] = "#addt";
        $this->conf["ignore"]["chans"][] = "#coders";
        $this->conf["ignore"]["chans"][] = "#coders2";
        $this->conf["ignore"]["chans"][] = "#addpre.backfill";
        // Nicks to ignore
        $this->conf["ignore"]["nicks"][] = "layer13";
        $this->conf["ignore"]["nicks"][] = "l13a";
        $this->conf["ignore"]["nicks"][] = "l13d";
        $this->conf["ignore"]["nicks"][] = "l13c";


        $this->conf["youtube_apikey"] = "AIzaSyDtmgm9nJRQhWg4j1SetCH-vDMZ3_UZfK0"; // to get your own key, create a project at https://console.developers.google.com to get apikey "bs google"
        $this->conf["steam_bundledetails"] = "count"; // can be list or count (warning: list can be loooong)
    }

    public function priv_urlreveal($line, $args)
    {

        $channel = $line['to'];
        $text = $line['text'];
        $fromNick = $line['fromNick'];

        //failsafes
        if (strpos($channel, "#") === false)
            return;

        if (preg_grep("/$channel/i", $this->conf["ignore"]["chans"]))
            return;

        if (preg_grep("/$fromNick/i", $this->conf["ignore"]["nicks"]))
            return;

        $preg = "(((ht){1}tp(s|):\/\/|www\.)[-a-zA-Z0-9@:%_\+.~#?&\/\/=]+)"; //regex for url reconizing
        if (preg_match("/$preg/i", $text, $matches)) { // fix better url reconizing
            $url = $matches[0];
            $res = $this->get_url_contents("$url"); // get url contents

            $nurl = urldecode($res["url"]); // set new "niceurl"

            $temp = parse_url($url);
            $host = $temp["host"];
            // who to blame when we dont have title ;)
            $blame = explode(".", $host);
            $blame = $blame[count($blame) - 2];

            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($res["html"]);


            $list = $dom->getElementsByTagName("title");
            if ($list->length > 0)
                $title = html_entity_decode(str_replace(array("\n", '\r'), "", $list->item(0)->textContent));

            if (strlen($title) == 0)
                $title = "NO FkInG <TiTLE> WeB 2.0 Now without title tags ... good job $blame";

            if (preg_match("/image/i", $res["type"])) {
                // url is image return img stats
                $surl = substr(str_replace($urlstrip, "", $url), 0, 20);
                $nsurl = substr(str_replace($urlstrip, "", $nurl), 0, 20);
                if ($surl[strlen($surl) - 1] == "/")
                    $surl = substr($surl, 0, strlen($surl) - 1);
                if ($nsurl[strlen($nsurl) - 1] == "/")
                    $nsurl = substr($nsurl, 0, strlen($nsurl) - 1);

                if ($surl != $nsurl)
                    $urlinfo = "$surl redirects to $nsurl";
                else
                    $urlinfo = "$nhost";

                $myFile = "tmpimg";
                $fh = fopen($myFile, 'w');
                fwrite($fh, $res["html"]);
                fclose($fh);

                list($width, $height, $type, $attr) = getimagesize($myFile);

                $this->ircClass->privMsg($channel, "image url ($urlinfo) imgsize $width" . "x" .
                    "$height in " . mksize($res[size]) . " 7Speed: " . $this->mksize($res["speed"]) .
                    "/s 15 other stats [type $res[type] / dns-lookup $res[dnslookup] / wasted $res[connection] + $res[redirtime]]");
                unlink("tmpimg");
            } else {
                if ($title) { // if we got a title its a page return info
                    $urlstrip = array("http://", "www.", "https://", "HTTP://", "WWW.", "HTTPS://"); // stripcode to make urls nice

                    if (strlen($title) > 185)
                        return false; // omg, nononononno

                    $surl = substr(str_replace($urlstrip, "", $url), 0, 22);
                    $nsurl = substr(str_replace($urlstrip, "", $nurl), 0, 22);
                    if ($surl[strlen($surl) - 1] == "/")
                        $surl = substr($surl, 0, strlen($surl) - 1);
                    if ($nsurl[strlen($nsurl) - 1] == "/")
                        $nsurl = substr($nsurl, 0, strlen($nsurl) - 1);

                    if (trim($surl) != trim($nsurl))
                        $urlinfo = "$surl redirects to $nsurl";
                    else $urlinfo = "$surl";

                    // extensions/plugins ...

                    // Steam store .
                    if (preg_match("/store.steampowered.com\/sub/i", $url)) {
                        // bundle
                        $appID = preg_match("/\/([0-9]+)/i", $url, $matches);
                        $appID = (int)trim($matches[1]);
                        echo "APPiD -$appID-\n";
                        if (is_int($appID) AND $appID > 0) {
                            $storeurl = "http://store.steampowered.com/api/packagedetails/?packageids=$appID";
                            $storeinfo = json_decode(file_get_contents($storeurl), true);
                            $storeinfo = $storeinfo[$appID]["data"];
                            print_r($storeinfo);

                            $cur = ($storeinfo["price"]['currency'] == "EUR" ? "€" : ($storeinfo["price"]['currency'] == "USD" ? "$" : $storeinfo["price"]['currency']));

                            if ($storeinfo["price"]["initial"] > $storeinfo["price"]["final"]) {
                                // discount
                                $price = "On sale " . round($storeinfo["price"]["final"] / 100, 2) . "$cur";
                                $onsale = " (Save " . $storeinfo["price"]['discount_percent'] . "% - Down from " . round($storeinfo["price"]['initial'] / 100, 2) . "$cur)";
                            } else {
                                $price = "" . round($storeinfo["price"]["final"] / 100, 2) . "$cur";
                            }


                            foreach ($storeinfo['apps'] as $tmp) {
                                $incapps[] = $tmp["name"];
                            }

                            if ($this->conf["steam_bundledetails"] == "list")
                                $included = implode(", ", $incapps);
                            if ($this->conf["steam_bundledetails"] == "count")
                                $included = count($storeinfo['apps']) . " Items";


                            $title = $storeinfo['name'] . "$onsale";
                            $sumup = "7Price: $price 7Includes: $included";

                        }
                    }
                    if (preg_match("/store.steampowered.com(\/app|\/agecheck\/app)/i", $url)) {
                        // app
                        $appID = preg_match("/\/([0-9]+)/i", $url, $matches);
                        $appID = (int)trim($matches[1]);
                        echo "APPiD -$appID-\n";
                        if (is_int($appID) AND $appID > 0) {

                            $storeurl = "http://store.steampowered.com/api/appdetails?appids=$appID";
                            $storeinfo = json_decode(file_get_contents($storeurl), true);

                            $d = $storeinfo[$appID]["data"];
                            print_r($storeinfo);

                            echo "got store data ..";
                            $app['name'] = $d['name'];
                            $app['www'] = $d["website"];

                            $app["metacritic"]["score"] = $d["metacritic"]["score"];

                            $cur = ($d['price_overview']['currency'] == "EUR" ? "€" : ($d['price_overview']['currency'] == "USD" ? "$" : $d['price_overview']['currency']));
                            if ($d['price_overview']['initial'] > $d['price_overview']['final']) {
                                // app is on sale

                                $price = "On sale " . round($d['price_overview']['final'] / 100, 2) . "$cur";
                                $onsale = " (Save " . $d['price_overview']['discount_percent'] . "% - Down from " . round($d['price_overview']['initial'] / 100, 2) . "$cur)";

                            } else {
                                // no discount

                                if ($d['price_overview']['final'] == 0)
                                    $price = "Free To Play";
                                else
                                    $price = round($d['price_overview']['final'] / 100, 2) . "$cur";
                            }

                            foreach ($d['categories'] as $cat) {
                                if ($cat["description"] == "Single-player")
                                    $app['cat'][] = "Singleplayer";
                                if ($cat["description"] == "Multi-player")
                                    $app['cat'][] = "Multiplayer";
                                if ($cat["description"] == "MMO")
                                    $app['cat'][] = "MMORPG";
                                if ($cat["description"] == "Co-op")
                                    $app['cat'][] = "CoOp";
                                if ($cat["description"] == "Single-player") $app['cats'] = "Singleplayer";
                                if ($cat["description"] == "Single-player") $app['cats'] = "Singleplayer";
                                if ($cat["description"] == "Single-player") $app['cats'] = "Singleplayer";
                            }
                            $app['cat'] = implode("/", $app['cat']);


                            foreach ($d['genres'] as $genre)
                                $genres[] = str_replace(array("Free to Play", "Massively Multiplayer"), array("F2P",
                                    "MMO"), $genre["description"]);
                            $app["genre"] = implode("/", $genres);

                            if ($d['platforms']['windows'])
                                $app['platform'][] = "Win";
                            if ($d['platforms']['mac'])
                                $app['platform'][] = "Mac";
                            if ($d['platforms']['linux'])
                                $app['platform'][] = "Linux";
                            $app['platform'] = implode("/", $app["platform"]);

                            if ($d['publishers'][0] == $d['developers'][0])
                                $pub = $d['developers'][0];
                            else
                                $pub = $d['publishers'][0] . "/" . $d['developers'][0];

                            // pre purchase ?
                            $pre = ($d["release_date"]["coming_soon"] == 1 ? "(Pre-Purchase, Avaible ".$d['release_date']['date'].") ": "");

                            $title = $pub . " Presents " . $pre.($d["type"] == "dlc" ? "[" . $d["fullgame"]["name"] . "/DLC] " . str_replace($d["fullgame"]["name"] . ":", "", $app["name"]) : $app["name"]) . $onsale;

                            if (strlen($d['website']) > 0)
                                $www = " 7Website: " . $d['website'];


                            $sumup = (strlen($app["cat"]) > 0 ? "7Cat: $app[cat] - " : "") . "7Platform: $app[platform] - 7Genre: $app[genre] - 7Price: " . $price . $www;
                        }
                    }


                    if (preg_match("/youtube/i", $title)) {
                        // api override for youtube since have captha
                        $purl = parse_url($url);
                        $purl = explode("&", $purl["query"]);

                        foreach ($purl as $pu)
                            if (substr($pu, 0, 2) == "v=")
                                $vidID = substr($pu, 2);

                        $apiurl = "https://www.googleapis.com/youtube/v3/videos?part=snippet&id=" . $vidID . "&key=" . $this->conf["youtube_apikey"];
                        $data = json_decode(file_get_contents($apiurl));
                        $data = $data->items[0]->snippet;

                        $title = $data->title . " - 1,0 You0,4tube ";

                        if ($data->title != $data->description)
                            $sumup = "7Description: " . $data->description;

                    }


                    $wasted = $res['connection'] + $res['redirtime'];
                    $this->ircClass->privMsg($channel, "7URL $urlinfo 7Title: $title - 7Speed: " .
                        $this->mksize($res['size']) . "@" . $this->mksize($res["speed"]) .
                        "/s 15 other stats [type: $res[type] / dns-lookup: $res[dnslookup] / wasted: $wasted]");

                    if (strlen(@$sumup) >= 2 and strlen(@$sumup) <= 512)
                        $this->ircClass->privMsg($channel, "$sumup");
                }
            }
        }
    }

    // internal
    public function get_url_contents($url, $timeout = 30)
    {
        global $urlconf;
        $crl = curl_init();
        $header[] = "Connection: keep-alive";
        $header[] = "keep-alive: $timeout";
        $header[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
        $header[] = "Accept-Language: en-gb,en;q=0.5";
        $header[] = "Accept-Encoding: gzip,deflate";
        $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
        $header[] = "Pragma: "; // browsers keep this blank.

        //if ($urlconf["usecookies"]) {
        $cookie = './cookies/cookie.txt';
        if (!file_exists("./cookies"))
            mkdir("./cookies");
        curl_setopt($crl, CURLOPT_COOKIEFILE, $cookie);
        curl_setopt($crl, CURLOPT_COOKIEJAR, $cookie);
        //}
        curl_setopt($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($crl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($crl, CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-GB; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3');
        curl_setopt($crl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($crl, CURLOPT_URL, $url);
        curl_setopt($crl, CURLOPT_VERBOSE, false);
        curl_setopt($crl, CURLOPT_HEADER, false);
        curl_setopt($crl, CURLOPT_ENCODING, "");
        curl_setopt($crl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($crl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);


        $ret["html"] = curl_exec($crl);
        $ret["url"] = curl_getinfo($crl, CURLINFO_EFFECTIVE_URL);
        $ret["speed"] = curl_getinfo($crl, CURLINFO_SPEED_DOWNLOAD);
        $ret["size"] = curl_getinfo($crl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        $ret["type"] = curl_getinfo($crl, CURLINFO_CONTENT_TYPE);

        $ret["redirtime"] = number_format(curl_getinfo($crl, CURLINFO_REDIRECT_TIME), 2);
        $ret["dnslookup"] = number_format(curl_getinfo($crl, CURLINFO_NAMELOOKUP_TIME), 2);
        $ret["connection"] = number_format(curl_getinfo($crl, CURLINFO_CONNECT_TIME), 2);

        curl_close($crl);
        return $ret;
    }

    public function extractstring($start, $end, $text, $casesens = "no")
    {
        if ($casesens == "no")
            $ext = "i";
        if ($casesens == "yes")
            $ext = "";
        preg_match("/$start/$ext", $text, $smatches, PREG_OFFSET_CAPTURE);
        $ss = $smatches[0][1];
        preg_match("/$end/$ext", $text, $smatches, PREG_OFFSET_CAPTURE, $ss);
        $se = $smatches[0][1];
        return str_replace(array(strtolower("$start"), strtoupper("$start"), strtolower
        ("$end"), strtoupper("$end"), "\r", "\n"), "", substr($text, $ss, $se - $ss));
    }

    function mksize($bytes)
    {
        if ($bytes < 1000 * 1024)
            return number_format($bytes / 1024, 2) . " kB";
        elseif ($bytes < 1000 * 1048576)
            return number_format($bytes / 1048576, 2) . " MB";
        elseif ($bytes < 1000 * 1073741824)
            return number_format($bytes / 1073741824, 2) . " GB";
        else
            return number_format($bytes / 1099511627776, 2) . " TB";
    }
}

?>