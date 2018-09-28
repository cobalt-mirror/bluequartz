<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * DockerLibs.php
 *
 * Class to deal with Docker management tasks.
 *
 * @package   DockerLibs
 * @author    Michael Stauber
 * @link      http://www.solarspeed.net
 * @version   1.0
 */

class DockerLibs {

    //
    // private variables
    //

    var $cceClient;
    var $loginName;
    var $sessionId;
    var $i18n;
    var $serverScriptHelper;

    // Description: Constructor
    // Params: An active serverScriptHelper, active cceclient, loginName and sessionId:
    function DockerLibs($SCH = NULL, $cce = NULL, $uname = NULL, $sessID = NULL, $sessi18n = NULL) {

        if ($SCH != NULL) {
            $this->serverScriptHelper =& $SCH;
        }
        if ($cce != NULL) {
            $this->cceClient =& $cce;
        }
        if ($uname != NULL) {
            $this->loginName =& $uname;
        }
        if ($sessID != NULL) {
            $this->sessionId =& $sessID;
        }
        if ($sessi18n != NULL) {
            $this->i18n =& $sessi18n;
        }
        else {
            $this->i18n = new I18n("base-docker", 'en_US');
        }
    }

    // description: GetDockerList()
    public function GetDockerList($c_short = TRUE) {

        $columns = array('CONTAINER ID', 'IMAGE', 'COMMAND', 'CREATED', 'STATUS', 'PORTS', 'NAMES', 'SIZE');
        $ret = $this->serverScriptHelper->shell("/usr/bin/docker ps -a -s | sed 's/^[ \t]*//;s/[ \t]*\$//'", $dockerList_raw, 'root', $this->sessionId);

        $dockerList = array();
        $dockerList_inst = explode(PHP_EOL, $dockerList_raw);

        $e = '0';
        $start['CTID'] = '0';
        $stop['CTID'] = '';
        $matches = '';
        preg_match('/IMAGE/i', $dockerList_inst[0], $matches, PREG_OFFSET_CAPTURE);
        if (isset($matches[0][1])) {
            $start['IMAGE'] = $matches[0][1];
            $stop['CTID'] = $matches[0][1]-$start['CTID'];
        }
        preg_match('/COMMAND/i', $dockerList_inst[0], $matches, PREG_OFFSET_CAPTURE);
        if (isset($matches[0][1])) {
            $start['COMMAND'] = $matches[0][1];
            $stop['IMAGE'] = $matches[0][1]-$start['IMAGE'];
        }
        preg_match('/CREATED/i', $dockerList_inst[0], $matches, PREG_OFFSET_CAPTURE);
        if (isset($matches[0][1])) {
            $start['CREATED'] = $matches[0][1];
            $stop['COMMAND'] = $matches[0][1]-$start['COMMAND'];
        }
        preg_match('/STATUS/i', $dockerList_inst[0], $matches, PREG_OFFSET_CAPTURE);
        if (isset($matches[0][1])) {
            $start['STATUS'] = $matches[0][1];
            $stop['CREATED'] = $matches[0][1]-$start['CREATED'];
        }
        preg_match('/PORTS/i', $dockerList_inst[0], $matches, PREG_OFFSET_CAPTURE);
        if (isset($matches[0][1])) {
            $start['PORTS'] = $matches[0][1];
            $stop['STATUS'] = $matches[0][1]-$start['STATUS'];
        }
        preg_match('/NAMES/i', $dockerList_inst[0], $matches, PREG_OFFSET_CAPTURE);
        if (isset($matches[0][1])) {
            $start['NAMES'] = $matches[0][1];
            $stop['PORTS'] = $matches[0][1]-$start['PORTS'];
        }
        preg_match('/SIZE/i', $dockerList_inst[0], $matches, PREG_OFFSET_CAPTURE);
        if (isset($matches[0][1])) {
            $start['SIZE'] = $matches[0][1];
            $stop['NAMES'] = $matches[0][1]-$start['NAMES'];
        }

        if ($stop['CTID'] != '') {
            foreach ($dockerList_inst as $key => $listData) {
                $CTID = substr($listData, $start['CTID'], $stop['CTID']);
                $CTID = preg_replace('/\s+/', '', $CTID);
                if (ctype_xdigit($CTID)) {
                    $IMAGE = mb_substr($listData, $start['IMAGE'], $stop['IMAGE']);
                    $IMAGE = preg_replace('/\s+/', '', $IMAGE);

                    $COMMAND = mb_substr($listData, $start['COMMAND'], $stop['COMMAND']);
                    $COMMAND = preg_replace('/\s+/', ' ', $COMMAND);

                    $CREATED = mb_substr($listData, $start['CREATED'], $stop['CREATED']);
                    $CREATED = preg_replace('/\s+/', ' ', $CREATED);

                    $STATUS = mb_substr($listData, $start['STATUS'], $stop['STATUS']);
                    $STATUS = preg_replace('/\s+/', ' ', $STATUS);

                    $PORTS = mb_substr($listData, $start['PORTS'], $stop['PORTS']);
                    $PORTS = preg_replace('/\s+/', ' ', $PORTS);

                    $NAMES = mb_substr($listData, $start['NAMES'], $stop['NAMES']);
                    $NAMES = preg_replace('/\s+/', ' ', $NAMES);

                    $SIZE = mb_substr($listData, $start['SIZE'], strlen($listData));
                    $SIZE = preg_replace('/\s+/', ' ', $SIZE);

                    $dockerList[$e] = array(
                            'CTID' => $CTID,
                            'IMAGE' => $IMAGE,
                            'COMMAND' => $COMMAND,
                            'CREATED' => $CREATED,
                            'STATUS' => $STATUS,
                            'PORTS' => $PORTS,
                            'NAMES' => $NAMES,
                            'SIZE' => $SIZE
                        );
                    $e++;
                }
            }
        }
        return $dockerList;
    }

    // description: GetDockerImages()
    public function GetDockerImages($c_short = TRUE) {

        $columns = array('REPOSITORY', 'TAG', 'IMAGE ID', 'CREATED', 'SIZE');
        $ret = $this->serverScriptHelper->shell("/usr/bin/docker images | sed 's/^[ \t]*//;s/[ \t]*\$//'", $dockerList_raw, 'root', $this->sessionId);

        $dockerList = array();
        $dockerList_inst = explode(PHP_EOL, $dockerList_raw);

        $e = '0';
        $start['REPOSITORY'] = '0';
        $stop['REPOSITORY'] = '';
        $matches = '';
        preg_match('/TAG/i', $dockerList_inst[0], $matches, PREG_OFFSET_CAPTURE);
        if (isset($matches[0][1])) {
            $start['TAG'] = $matches[0][1];
            $stop['REPOSITORY'] = $matches[0][1]-$start['REPOSITORY'];
        }
        preg_match('/IMAGE/i', $dockerList_inst[0], $matches, PREG_OFFSET_CAPTURE);
        if (isset($matches[0][1])) {
            $start['IMAGE_ID'] = $matches[0][1];
            $stop['TAG'] = $matches[0][1]-$start['TAG'];
        }
        preg_match('/CREATED/i', $dockerList_inst[0], $matches, PREG_OFFSET_CAPTURE);
        if (isset($matches[0][1])) {
            $start['CREATED'] = $matches[0][1];
            $stop['IMAGE_ID'] = $matches[0][1]-$start['IMAGE_ID'];
        }
        preg_match('/SIZE/i', $dockerList_inst[0], $matches, PREG_OFFSET_CAPTURE);
        if (isset($matches[0][1])) {
            $start['SIZE'] = $matches[0][1];
            $stop['CREATED'] = $matches[0][1]-$start['CREATED'];
        }

        if ($stop['REPOSITORY'] != '') {
            foreach ($dockerList_inst as $key => $listData) {

                if ((!preg_match('/^REPOSITORY(.*)$/', $listData)) && (strlen($listData) != '0')) {

                    $REPOSITORY = substr($listData, $start['REPOSITORY'], $stop['REPOSITORY']);
                    $REPOSITORY = preg_replace('/\s+/', '', $REPOSITORY);

                    $TAG = mb_substr($listData, $start['TAG'], $stop['TAG']);
                    $TAG = preg_replace('/\s+/', '', $TAG);

                    $IMAGE_ID = mb_substr($listData, $start['IMAGE_ID'], $stop['IMAGE_ID']);
                    $IMAGE_ID = preg_replace('/\s+/', '', $IMAGE_ID);

                    $CREATED = mb_substr($listData, $start['CREATED'], $stop['CREATED']);
                    $CREATED = preg_replace('/\s+/', ' ', $CREATED);

                    $SIZE = mb_substr($listData, $start['SIZE'], strlen($listData));
                    $SIZE = preg_replace('/\s+/', ' ', $SIZE);

                    $dockerList[$e] = array(
                            'REPOSITORY' => $REPOSITORY,
                            'TAG' => $TAG,
                            'IMAGE_ID' => $IMAGE_ID,
                            'CREATED' => $CREATED,
                            'SIZE' => $SIZE
                        );
                    $e++;
                }
            }
        }
        return $dockerList;
    }

    // description: SearchDockerImageTags() - get all TAGs for an image:
    public function SearchDockerImageTags($Search = '') {
        $url = 'https://registry.hub.docker.com/v1/repositories/' . $Search . '/tags';
        $TagData = get_data($url, "45"); // 45 seconds timeout for download
        $TagData = json_decode($TagData, TRUE);
        $outTag = array();
        $returnTag = array();

        // Get Name and Description of toplevel image:
        $x = $this->SearchDockerImages($Search);
        if (isset($x['0'])) {
            foreach ($x as $key => $value) {
                if (isset($x[$key]['NAME'])) {
                    if ($x[$key]['NAME'] == $Search) {
                        $outTag = $x[$key];
                    }
                }
            }
        }

        // Splice tags, name and desciption together into a new unified output:
        foreach ($TagData as $key => $value) {
            if (isset($value['name'])) {
                $returnTag[$key]['NAME'] = $Search . ':' . $value['name'];
                $returnTag[$key]['DESCRIPTION'] = $outTag['DESCRIPTION'];
                $returnTag[$key]['STARS'] = $outTag['STARS'];
                $returnTag[$key]['OFFICIAL'] = $outTag['OFFICIAL'];
                $returnTag[$key]['AUTOMATED'] = $outTag['AUTOMATED'];
            }
        }

        if (is_array($returnTag)) {
            return $returnTag;
        }
        else {
            return array();
        }
    }

    // description: SearchDockerImages()
    public function SearchDockerImages($Search = '') {

        if (isset($_COOKIE['SLdisplay'])) {
          $SLdisplay = $_COOKIE['SLdisplay'];
        }
        else {
            $SLdisplay = '25';
        }

        $dockerList = array();

        if ($Search != '') {
            $columns = array('REPOSITORY', 'TAG', 'IMAGE ID', 'CREATED', 'SIZE');
            $ret = $this->serverScriptHelper->shell("/usr/bin/docker search $Search --limit $SLdisplay --no-trunc | sed 's/^[ \t]*//;s/[ \t]*\$//'", $dockerList_raw, 'root', $this->sessionId);

            $dockerList_inst = explode(PHP_EOL, $dockerList_raw);

            $e = '0';
            $start['NAME'] = '0';
            $stop['NAME'] = '';
            $matches = '';
            preg_match('/DESCRIPTION/i', $dockerList_inst[0], $matches, PREG_OFFSET_CAPTURE);
            if (isset($matches[0][1])) {
                $start['DESCRIPTION'] = $matches[0][1];
                $stop['NAME'] = $matches[0][1]-$start['NAME'];
            }
            preg_match('/STARS/i', $dockerList_inst[0], $matches, PREG_OFFSET_CAPTURE);
            if (isset($matches[0][1])) {
                $start['STARS'] = $matches[0][1];
                $stop['DESCRIPTION'] = $matches[0][1]-$start['DESCRIPTION'];
            }
            preg_match('/OFFICIAL/i', $dockerList_inst[0], $matches, PREG_OFFSET_CAPTURE);
            if (isset($matches[0][1])) {
                $start['OFFICIAL'] = $matches[0][1];
                $stop['STARS'] = $matches[0][1]-$start['STARS'];
            }
            preg_match('/AUTOMATED/i', $dockerList_inst[0], $matches, PREG_OFFSET_CAPTURE);
            if (isset($matches[0][1])) {
                $start['AUTOMATED'] = $matches[0][1];
                $stop['OFFICIAL'] = $matches[0][1]-$start['OFFICIAL'];
            }

            if ($stop['NAME'] != '') {
                foreach ($dockerList_inst as $key => $listData) {

                    if ((!preg_match('/^NAME(.*)$/', $listData)) && (strlen($listData) != '0')) {

                        $NAME = substr($listData, $start['NAME'], $stop['NAME']);
                        $NAME = preg_replace('/\s+/', '', $NAME);

                        $DESCRIPTION = mb_substr($listData, $start['DESCRIPTION'], $stop['DESCRIPTION']);
                        $DESCRIPTION = preg_replace('/\s+/', ' ', $DESCRIPTION);

                        $STARS = mb_substr($listData, $start['STARS'], $stop['STARS']);
                        $STARS = preg_replace('/\s+/', '', $STARS);

                        $OFFICIAL = mb_substr($listData, $start['OFFICIAL'], $stop['OFFICIAL']);
                        $OFFICIAL = preg_replace('/\s+/', ' ', $OFFICIAL);

                        $AUTOMATED = mb_substr($listData, $start['AUTOMATED'], strlen($listData));
                        $AUTOMATED = preg_replace('/\s+/', ' ', $AUTOMATED);

                        $dockerList[$e] = array(
                                'NAME' => $NAME,
                                'DESCRIPTION' => $DESCRIPTION,
                                'STARS' => $STARS,
                                'OFFICIAL' => $OFFICIAL,
                                'AUTOMATED' => $AUTOMATED
                            );
                        $e++;
                    }
                }
            }
        }
        return $dockerList;
    }

    // description: DownloadDockerImage()
    public function DownloadDockerImage($dl = '') {
        if (strlen($dl) > '1') {
            $ret = $this->serverScriptHelper->shell("/usr/bin/docker pull $dl", $dockerList_raw, 'root', $this->sessionId);
        }
        return $ret;
    }

    // description: DeleteDockerImage()
    public function DeleteDockerImage($del = '') {
        if (strlen($del) > '1') {
            if (!preg_match('/:/', $del)) {
                $del = $del . ':latest';
            }
            $ret = $this->serverScriptHelper->shell("/usr/bin/docker rmi $del", $dockerList_raw, 'root', $this->sessionId);
        }
        return $ret;
    }

    // description: RestartDockerInstance()
    public function RestartDockerInstance($instance = '') {
        if (strlen($instance) > '1') {
            $ret = $this->serverScriptHelper->shell("/usr/bin/docker stop $instance", $dockerList_raw, 'root', $this->sessionId);
            $ret = $this->serverScriptHelper->shell("/usr/bin/docker start $instance", $dockerList_raw, 'root', $this->sessionId);
        }
        return $ret;
    }

    // description: StopDockerInstance()
    public function StopDockerInstance($instance = '') {
        if (strlen($instance) > '1') {
            $ret = $this->serverScriptHelper->shell("/usr/bin/docker stop $instance", $dockerList_raw, 'root', $this->sessionId);
        }
        return $ret;
    }

    // description: DeleteDockerInstance()
    public function DeleteDockerInstance($instance = '') {
        if (strlen($instance) > '1') {
            $ret = $this->serverScriptHelper->shell("/usr/bin/docker stop $instance", $dockerList_raw, 'root', $this->sessionId);
            $ret = $this->serverScriptHelper->shell("/usr/bin/docker rm $instance", $dockerList_raw, 'root', $this->sessionId);
        }
        return $ret;
    }

    // description: RunDockerImage()
    public function RunDockerImage($instance = '', $params = '', $name = '') {

        $params = implode(' ', $this->cceClient->scalar_to_array($params));
        $params = escapeshellcmd($params);

        if (strlen($instance) > '1') {
            if ($name == '1') {
                $ret = $this->serverScriptHelper->shell("/usr/bin/docker run $params --detach $instance 2>&1", $dockerResponse_raw, 'root', $this->sessionId);
            }
            else {
                $ret = $this->serverScriptHelper->shell("/usr/bin/docker run $params --detach --name $name $instance 2>&1", $dockerResponse_raw, 'root', $this->sessionId);
            }
        }
        if ($ret != '0') {
            $error = ErrorMessage($dockerResponse_raw);
            return $error;
        }
        else {
            return $ret;
        }
    }

    // description: DockerInspect()
    public function DockerInspect($instance = '') {
        if (strlen($instance) > '1') {
            $ret = $this->serverScriptHelper->shell("/usr/bin/docker inspect $instance", $dockerList_raw, 'root', $this->sessionId);
        }
        $decoded = json_decode($dockerList_raw);
        if (isset($decoded[0])) {
            if (is_object($decoded[0])) {
                return $decoded;
            }
            else {
                return '-1';
            }
        }
        else {
            return '-1';
        }
    }

    // description: KernelVersion()
    public function KernelVersion() {
        $ret = $this->serverScriptHelper->shell("/usr/bin/uname -r|/usr/bin/cut -d - -f1", $KV, 'root', $this->sessionId);
        $KV = rtrim($KV);
        $diff = version_compare($KV, "3.1.0");
        $ret = '0';
        if (($diff == "1") || ($diff == "0")) {
            $ret = '1';
        }
        return $ret;
    }
}

/*
Copyright (c) 2018 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2018 Team BlueOnyx, BLUEONYX.IT
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/
?>