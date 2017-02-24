<?php
/**
 * Very simple & fast tree
 * usable for threaded discussion forums
 */
// -- tree definition
/**
 * tree node
 */
class node {
    // payload - place for your (db)fields

    // core

    #region dbFields
    /**
     * @var string unique node ID
     */
    public $UID;
    /**
     * @var string parent node ID
     */
    public $parentUID;
    /**
     * @var int creation time (unix time stamp)
     */
    public $cTime;
    /**
     * @var int node level within tree
     */
    public $nodeLevel;

    /**
     * @var string Root node uid - distinguishes trees
     */
    protected $ROOTnode;
    #endregion

    // runtime
    private $parentPtr;
    private $prevNode;
    private $nextNode;

    private $rootPath;
    private $contentNodes;
    private $_lastInsertedPtr;

    protected static $ROOT;

    /**
     * node constructor.
     * @param $UID - unique text key
     * @param $payloadFields - assoc. array of payload fields from definition
     */
    public function __construct($UID, $payloadFields = NULL) {
        // echo "NODE: $UID\n";
        $this->contentNodes = array();
        $this->UID = $UID;
        $this->ctime = time();

        $this->prevNode = NULL;
        $this->nextNode = NULL;

        $this->rootPath = array();

        // payload settings - direct hard setting is more optimal
        if (!is_null($payloadFields))
            foreach($payloadFields as $f => $v){
                if(property_exists((get_class($this)),$f)) /// can be omitted if sure $payloadFields contains valid fields
                    $this->$f = $v;
            }
    }

    /**
     * adds node
     * @param object $node - instantiated node object
     * @return mixed - link to the added node
     */
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

    /**
     * remeves node from tree including subnodes
     * @param $nodeUID
     */
    public function remove ( $nodeUID ){
        $this->contentNodes[$nodeUID] = NULL;
    }

    /**
     * renders tree node visually
     * @param string $target
     */
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

    /**
     * destructor
     */
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

    /**
     * property getter
     * @param $prop
     * @return mixed
     */
    public function __get($prop){
        if (property_exists($this,$prop))
            return $this->$prop;
    }
}

/**
 * Class root
 *
 */
class root extends node {

    /**
     * node cache && flat accessor to nodes
     * @var array
     */
    public $flatNodes;

    /**
     * level based cache - not yet used
     * @var array
     */
    private $levelCache;

    /**
     * db link
     * @var link
     */
    private static $db = NULL;

    /**
     * root constructor.
     * @param $UID
     */
    public function __construct($UID){
        parent::__construct($UID);
        $this->flatNodes = array();
        node::$ROOT = &$this;
    }

    /**
     * @param $node
     * @param null $parentNode
     * @throws Exception when duplicity found
     */
    public function addNode($node,$parentNode = NULL){

        if(isset($this->flatNodes[$node->UID])){
            throw new Exception('Node alredy exists! '."\n node: ".$node->UID." - pNode:".$parentNode->UID."\n" );
        }

        if(!is_null($parentNode)){
           $parentNode -> add($node);
        }else{
            $this->flatNodes[$node->UID] = &$node;
        }
    }

    /**
     *
     * @param $nodeUID
     */
    public function dropNode($nodeUID){

        $this->flatNodes[$nodeUID] = NULL;

    }

    /**
     * strores node in database
     * @param $node
     */
    public static function dbSaveNode($node){

    }

    /**
     * deletes node from database
     * @param $nodeUID
     */
    public static function dbDropNode ($nodeUID){

    }

    /**
     * loads complete tree from db and
     * @return $tree
     */
    public static function dbLoadData(){

        $tree = new root();
        return $tree;
    }
}


///
// MySQL definitions
/*


 */

// -- api - controller


// -- tests - runs from commandline, creates 5000 nodes tree and dumps it to the console
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

define ('DEMO_NODES',500);

$_uid_cache = array();
echo "building UID cache ... ";
for ($i=0;$i<DEMO_NODES;$i++) {
    $__uid = uniqid('');
    $_uid_cache[] = $__uid;
}

echo " done\n";

$time_start = microtime_float();
$_all_nodes = array();
try {

    for ($_i = 0; $_i < DEMO_NODES; $_i++) {
        $node = null;

        $_pnode_pos = rand(0, count($root->flatNodes));

        if ($_pnode_pos != 0) {
            $pnode = $_all_nodes[$_pnode_pos];
        } else {
            $pnode = null;
        }

        $s = microtime_float();
        $uid = $_uid_cache[$_i];
        $node = new node($uid);

        $_uid_cache[$i] = null;

        $globals['ut_node_create'] =+ (microtime_float() - $s);

        $_all_nodes[] = $node;


        $s = microtime_float();
        if (is_null($pnode)) {
            $root->add($node);
        } else {
            $root->addnode($node,$pnode);
        }
        $globals['ut_node_add'] += (microtime_float() - $s);

    }
    $time_build = microtime_float();

}catch(Exception $ex){
//    print_r($root);
    echo "\n------------------------------------\n\n";
  print_r($ex->getMessage());
}

$root->render('text');
$time_render = microtime_float();

echo "built: ".($time_build - $time_start)."\n";
echo "rendered: ".($time_render - $time_build)."\n";

echo "creating nodes:$ut_node_create \n";
echo "adding nodes:$ut_node_add \n";
?>