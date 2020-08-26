<?
namespace MagicFlatsFinder\base;

class ElasticSearch extends \Elasticsearch\ClientBuilder
{
    public $client = false;

    public function cloud( $cloudId, $username, $password )
    {
        $this->client = parent::create()
            ->setElasticCloudId($cloudId)
            ->setBasicAuthentication($username, $password)
            ->build();

        return $this->client;
    }

    public static function checkIssetCloudParams($params)
    {
        if( isset( $params ) || is_array($params) ) {
            foreach (['cloudId', 'username', 'password'] as $key) {
                if( !isset( $params[$key] ) || !$params[$key] || is_null($params[$key]) )
                    return false;
            }
            return true;
        }
        return false;
    }


    public static function getIndexName($project,$type)
    {
        return 'index_'.$project.'_'.$type;
    }

    public function search( $params, $item_id = true, $item_info=false, $full=false )
    {
        $res = $this->client->search( $params );

        if( $full )
            return $res;

        $_res = [];
        if( !isset($res['hits']['total']['value']) || (int) $res['hits']['total']['value'] === 0 )
            return $_res;

        foreach ( $res['hits']['hits'] as $i_k => $item ) {
            $id = $item_id ? $item['_id'] : $i_k;

            $_item = !$item_info ? $item['_source'] : $item;

            $_res[ $id ] = $_item;
        }

        return $_res;
    }

    public function indexExists( $index )
    {
        $arIndex = $this->client->indices()->getMapping();
        return key_exists( $index, $arIndex );
    }

    public function createIndex( $index, $body )
    {
        $params = [
            'index' => $index,
            'body' => $body
        ];

        return $this->client->indices()->create($params);
    }
}