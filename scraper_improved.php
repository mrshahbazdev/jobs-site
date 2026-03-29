<!DOCTYPE html>
<html>
<head>
    <title>Scraper Improved</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <?php 
    // Load dependencies (Ensure these paths are correct in your environment)
    @include 'translate/vendor/autoload.php'; 
    use \Statickidz\GoogleTranslate;
    @include 'userAgents.php';

    // Agent generation logic
    $userAgentString = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36";
    if (class_exists('userAgents')) {
        $agent = new userAgents();
        $userAgentString = $agent->generate();
    }

    $options = array(
        'http' => array(
            'method' => "GET",
            'header' => "Accept-language: en\r\n" .
                        "Cookie: time=" . md5(time()) . "\r\n" .
                        "User-Agent: " . $userAgentString . "\r\n"
        )
    );
    ?>

    <?php
    $servername = "127.0.0.1";
    $username = "jobspics_test";
    $password = "46464949Ali@";
    $dbname = "jobspics_test";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT siteName, postText FROM siteinfo LIMIT 1";
    $result = $conn->query($sql);
    $row = mysqli_fetch_assoc($result);
    $siteName = $row['siteName'] ?? 'JobSite';
    $postText = $row['postText'] ?? '';
    $conn->close();
    ?>
</head>
<body class="notranslate">

<?php
    // Dependencies (Ensure 'support' folder exists with these files)
    require_once "support/web_browser.php";
    require_once "support/tag_filter.php";

    $domain = $_SERVER['SERVER_NAME'];
    $htmloptions = TagFilter::GetHTMLOptions();

    if (isset($_GET['url'])) {
        $url = $_GET['url'];    
    } else {
        echo "Please provide a 'url' parameter.";
        exit();
    }

    $web = new WebBrowser();
    // Pass user-agent context if necessary
    $result = $web->Process($url);

    if (!$result["success"]) {
        echo "Error retrieving URL. " . $result["error"] . "\n";
        exit();
    }

    if ($result["response"]["code"] != 200) {
        echo "Error retrieving URL. Server returned: " . $result["response"]["code"] . "\n";
        exit();
    }

    $baseurl = $result["url"];
    $html = TagFilter::Explode($result["body"], $htmloptions);
    $root = $html->Get();

    // Data Extraction
    $title = $root->Find("#head-job-top h1");
    $detail = $root->Find("#job-detail-inner div");
    $detail3 = $root->Find("#j-desc p");
    $img = $root->Find(".image-container img[src]");
    $detail4 = $root->Find(".image-container");
    $tag2 = $root->Find(".innter-job-channel a:last-child");
    $description = $root->Find('meta[name="description"]');
    $script = $root->Find('script[type="application/ld+json"]');

    echo '<form method="post" action="https://'.$domain.'/admin/writepost" target="_blank" id="postForm">';
    
    // Process JSON-LD Schema for addresses
    foreach ($script as $value) {
        $json_text = $value->GetOuterHTML();
        if (strpos($json_text, 'jobLocation') !== false) {
            // Robust parsing would be better but keeping user's explode logic updated for safety
            $b = explode('jobLocation', $json_text);
            $n = explode('"baseSalary": {', $b[1]);
            $text = $n[0];
            $streetAddress = explode('"streetAddress" : "', $text);
            if(isset($streetAddress[1])) {
                $street1 = $streetAddress[1];
                $addressLocality = explode(':', $street1);
                $search = array('addressLocality','"',':','}','addressRegion','postalCode','addressCountry','streetAddress');
                $replace = array('','','','','','','','');
                $streetfinal = str_replace($search, $replace, $addressLocality[0]);
                $addressLocalityfinal = str_replace($search, $replace, $addressLocality[1] ?? '');
                $addressRegion = str_replace($search, $replace, $addressLocality[2] ?? '');
                $postalCode = str_replace($search, $replace, $addressLocality[3] ?? '');
                $addressCountry = str_replace($search, $replace, $addressLocality[4] ?? '');

                echo '<input type="hidden" name="street" value="'.rtrim(trim($streetfinal), ',').'">';
                echo '<input type="hidden" name="addressLocality" value="'.rtrim(trim($addressLocalityfinal), ',').'">';
                echo '<input type="hidden" name="addressRegion" value="'.rtrim(trim($addressRegion), ',').'">';
                echo '<input type="hidden" name="postalCode" value="'.rtrim(trim($postalCode), ',').'">';
                echo '<input type="hidden" name="addressCountry" value="'.preg_replace('/\s+/', '', rtrim(trim($addressCountry), ',')).'">';
            }
        }
    }

    $dv = '';
    foreach ($description as $value) {
        $dv .= $value->Node()['attrs']['content'];
    }

    echo '<div class="mb-3">
        <label class="form-label">Post Description</label>
        <textarea class="form-control" name="des">'.$siteName.' '. $dv.'</textarea>
    </div>';

    $tags = '';
    foreach ($tag2 as $value) {
        $tags .= $value->GetPlainText().',';
    }

    $t = '';
    foreach ($title as $value) {
        $t = $value->GetPlainText();
        $jobtitle = (strpos(strtolower($t), "job") === false) ? $t.' '.$siteName : 'Job '.$t.' '.$siteName;
        echo '<div class="mb-3">
            <label class="form-label">Post Title</label>
            <input type="text" class="form-control" name="title" value="'.$jobtitle.'">
        </div>';
    }

    $f1 = '';
    foreach ($detail3 as $values) {
        $f1 .= preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\r\n", $values->GetPlainText().'<br><br>');
    }

    $d7 = '';
    foreach ($detail as $value) { $d7 .= $value->GetOuterHTML(); }

    $search = ['<div id="job-detail-inner" class="fleft">', '<div class="job-inner1"', '<div class="job-inner3"', '</div', 'or as per date & details in paper ad.'];
    $replace = ['<table class="STYLED-TABLE">', '<tr><td class="strong"', '<td', '</td', $siteName];
    
    $d2 = '<table class="styled-table center"> <tbody>' . str_replace($search, $replace, $d7) . '</tbody> </table>';

    // Extraction helper for meta info
    $jobtype = @explode('Gender:', explode('Job Nature:', $d2)[1])[0];
    $location = @explode(',', explode('Job Location:', $d2)[1])[0];
    $education = @explode('Career Level:', explode('Education:', $d2)[1])[1] ?? '';
    $newspaper = @explode('Expected Last Date:', explode('Taken from Newspaper:', $d2)[1])[0] ?? 'Not Specified';

    $education = trim(preg_replace('/[\t\n\r\s]+/', ' ', str_replace(['<td>','</td>','<tr>','<td class="strong">'], '', strtolower($education))));
    $newspaper = trim(preg_replace('/[\t\n\r\s]+/', ' ', str_replace(['<td>','</td>','<tr>','<td class="strong">'], '', strtolower($newspaper))));
    $location = trim(str_replace(['<td>','</td>','<tr>','<td class="strong">'], '', $location));

    echo '<div class="mb-3">
        <label class="form-label">Newspaper</label>
        <input type="text" class="form-control" name="newspaper" value="'.$newspaper.'">
    </div>';
    echo '<div class="mb-3">
        <label class="form-label">Education</label>
        <input type="text" class="form-control" name="education" value="'.$education.'">
    </div>';
    echo '<div class="mb-3">
        <label class="form-label">Jobs Location</label>
        <input type="text" class="form-control" name="location" value="'.$location.'">
    </div>';

    echo '<div class="mb-3">
        <label class="form-label">Education Required</label>
        <input type="text" class="form-control" name="education" value="'.$education.'">
    </div>';

    echo '<div class="mb-3">
        <label class="form-label">Newspaper Name</label>
        <input type="text" class="form-control" name="newspaper" value="'.$newspaper.'">
    </div>';

    $ftags = $tags . str_replace(',', ' jobs,', $tags) . ' ' . $location . ' jobs, today jobs';
    echo '<div class="mb-3">
        <label class="form-label">Post Tags</label>
        <textarea class="form-control" name="tag">'.$ftags.'</textarea>
    </div>';

    // IMPROVED IMAGE PROCESSING SECTION
    $imgs = ''; 
    foreach ($img as $values) {
        $imageUrl = $values->src;
        
        // Use a more robust way to fetch and process images
        $image_content = @file_get_contents($imageUrl, false, stream_context_create($options));
        if ($image_content) {
            $im = @imagecreatefromstring($image_content);
            if ($im) {
                // IMPROVED WATERMARK REMOVAL LOGIC
                imagefilter($im, IMG_FILTER_GRAYSCALE);
                imagefilter($im, IMG_FILTER_BRIGHTNESS, 30);
                imagefilter($im, IMG_FILTER_CONTRAST, -45);
                imagefilter($im, IMG_FILTER_SMOOTH, 2);

                // Save as WebP for better performance
                $webpName = time().'.webp';
                $webpPath = '../assets/images/'.$webpName;
                if (!file_exists('../assets/images/')) { mkdir('../assets/images/', 0777, true); }
                imagewebp($im, $webpPath, 80);
                imagedestroy($im);

                echo ' <div class="mb-3">
                    <label class="form-label">Post img</label>
                    <input type="text" class="form-control" name="img" value="https://'.$domain.'/assets/images/'.$webpName.'">
                </div>';
                $imgs .= '<img src="https://'.$domain.'/assets/images/'.$webpName.'" class="img-fluid" alt="'.$t.'"><br>';
            }
        }
    }

    echo ' <div class="mb-3">
        <label class="form-label">Post Details</label>
        <textarea class="form-control" name="del1" style="height:100px">'.$f1.'</textarea>
        <textarea class="form-control" name="del" style="height:200px">'.$d2 . $imgs . $postText.'</textarea>
    </div>
    <input type="hidden" name="job" value="job">
    <button type="submit" class="btn btn-primary">Submit To Admin</button>
    </form>';
?>
<script type="text/javascript">
    // Auto-click submit and close (Uncomment when ready)
    // document.getElementById('postForm').submit();
    // setTimeout(function () { window.close(); }, 3000);
</script> 

</body>
</html>
