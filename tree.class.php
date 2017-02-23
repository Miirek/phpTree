<?php

// -- tree definition
class node {
    // payload

    // core
    // dbFields
    public $UID;
    public $parentUID;
    public $cTime;
    public $nodeLevel;

    // runtime
    private $parentPtr;
    private $prevNode;
    private $nextNode;

    private $rootPath;
    private $contentNodes;
    private $_lastInsertedPtr;

    protected static $ROOT;

    public function __construct($UID) {
        // echo "NODE: $UID\n";
        $this->contentNodes = array();
        $this->UID = $UID;
        $this->ctime = time();

        $this->prevNode = NULL;
        $this->nextNode = NULL;
    }

    public function add( $node ){
        $node->parentUID = $this->UID;
        $node->parentPtr = &$this;
        $node->level = ++$this->level;
        $node->rootPath = $this->rootPath;
        $node->rootPath[] = &$this;

        if($this->_lastInsertedPtr) {
            $_prev = $this->_lastInsertedPtr;
            $_prev->nextNode = &$node;
            $node->prevNode = $_prev;
        }
        $this->_lastInsertedPtr = &$node;
        $this->contentNodes[$node->UID] = &$node;

        self::$ROOT->addNode($node);

        return $this->contentNodes[$node->UID];

    }

    public function remove ( $nodeUID ){
        $this->contentNodes[$nodeUID] = NULL;
    }

    public function render( $target = 'JSON'){
        switch($target){
            case 'JSON':
                break;
            case 'text':
                $lastLevel = 0;
                foreach($this->rootPath as $pNode){
//                    for($i = ($pNode->level - $lastLevel);$i<$pNode->level-1;$i++)
//                        echo " ";
                    if($pNode->nextNode)
                        echo "|";
                    else
                        echo " ";

                    $lastLevel = $pNode->level;
                }
                // for($i = $this->parentPtr->level; $i< $this->level; $i++) echo " ";
                echo "+-> ".$this->UID."\n";
            break;

            default:
                echo "has ".count($this->contentNodes)." leaf(s)\n";
                break;
        }
        foreach($this->contentNodes as $uid=>$cNode)
            $cNode->render($target);

    }

    public function __destruct() {
        // TODO: Implement __destruct() method.
        foreach($this->contentNodes as $uid=>$node)
            $node = NULL;

        $this->contentNodes = NULL;
        $this->UID = NULL;
        $this->parentUID = NULL;
        $this->parentPtr = NULL;
        $this->level = NULL;
    }

    public function __get($prop){
        if (property_exists($this,$prop))
            return $this->$prop;
    }
}

class root extends node {

    public $flatNodes;
    private $levelCache;

    private static $db = NULL;

    public function __construct($UID){
        parent::__construct($UID);
        $this->flatNodes = array();
        node::$ROOT = &$this;
    }

    public function addNode($node,$parentNode = NULL){

        if(isset($this->flatNodes[$node->UID])){
            throw new Exception('Node alredy exists!');
        }

        if(!is_null($parentNode)){
           $parentNode -> add($node);
        }else{
            $this->flatNodes[$node->UID] = &$node;
        }
    }

    public function dropNode($nodeUID){

        $this->flatNodes[$nodeUID] = NULL;

    }

    public static function dbSaveNode($node){

    }

    public static function dbDropNode ($nodeUID){

    }

    public static function dbLoadData(){

    }
}


///
// SQL
/*
    C



 */

// -- api - controller


// -- tests
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

$ut_node_create = 0;
$ut_node_add = 0;
//$ut_root_add = 0;



$root  = new root(uniqid(''));

//print_r($root);

define ('DEMO_NODES',5000);

$_uid_cache = array();
echo "building UID cache ... ";
for ($i=0;$i<DEMO_NODES;$i++)
    $_uid_cache[] = uniqid('');
echo " done\n";

$time_start = microtime_float();
$_all_nodes = array();
try {

    for ($i = 0; $i < DEMO_NODES; $i++) {
        $node = null;

        $_pnode_pos = rand(0, floor(count($root->flatNodes)/1.5));

        if ($_pnode_pos != 0) {
            $pNode = $_all_nodes[$_pnode_pos];
        } else {
            $pNode = NULL;
        }

        $s = microtime_float();
        $node = new node($_uid_cache[$i], $pNode);
        $_uid_cache[$i] = NULL;
        $GLOBALS['ut_node_create'] =+ (microtime_float() - $s);
        $_all_nodes[] = $node;

        $s = microtime_float();
        if (is_null($pNode)) {
            $root->add($node);
        } else {
            $root->addNode($node,$pNode);
        }
        $GLOBALS['ut_node_add'] += (microtime_float() - $s);

    }
    $time_build = microtime_float();

}catch(Exception $ex){

    print_r($root);
    echo "\n------------------------------------\n\n";
    print_r($ex);


}

$root->render('text');

$time_render = microtime_float();

echo "built: ".($time_build - $time_start)."\n";
echo "rendered: ".($time_render - $time_build)."\n";

echo "creating nodes:$ut_node_create \n";
echo "adding nodes:$ut_node_add \n";
?>