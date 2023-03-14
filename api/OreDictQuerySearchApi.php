<?php

use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\NumericDef;

class OreDictQuerySearchApi extends ApiQueryBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'od');
    }

    public function getAllowedParams(): array {
        return array(
            'limit' => array(
                ParamValidator::PARAM_TYPE => 'limit',
				ParamValidator::PARAM_DEFAULT => 10,
                NumericDef::PARAM_MIN => 1,
                NumericDef::PARAM_MAX => ApiBase::LIMIT_BIG1,
                NumericDef::PARAM_MAX2 => ApiBase::LIMIT_BIG2,
            ),
            'prefix' => array(
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_DEFAULT => '',
            ),
            'mod' => array(
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_DEFAULT => '',
            ),
            'tag' => array(
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_DEFAULT => '',
            ),
            'name' => array(
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_DEFAULT => '',
            ),
            'from' => array(
				ParamValidator::PARAM_TYPE => 'integer',
				NumericDef::PARAM_MIN => 0,
                ParamValidator::PARAM_DEFAULT => 0,
            )
        );
    }

    public function getExamples(): array {
        return array(
            'api.php?action=query&list=oredictsearch&odmod=V&odtag=logWood',
            'api.php?action=query&list=oredictsearch&odname=Oak Wood Log&odmod=V',
        );
    }

    public function execute() {
        $prefix = $this->getParameter('prefix');
        $tag = $this->getParameter('tag');
        $mod = $this->getParameter('mod');
        $name = $this->getParameter('name');
        $limit = $this->getParameter('limit');
        $from = $this->getParameter('from');
        $dbr = $this->getDB();

        $conditions = array(
            "entry_id >= $from"
        );

        if ($prefix != '') {
            $conditions[] = 'item_name BETWEEN ' . $dbr->addQuotes($prefix) . " AND 'zzzzzzzzz'";
        }
        if ($tag != '') {
            $conditions['tag_name'] = $tag;
        }
        if ($mod != '') {
            $conditions['mod_name'] = $mod;
        }
        if ($name != '') {
            $conditions['item_name'] = $name;
        }

        $results = $dbr->select(
            'ext_oredict_items',
            '*',
            $conditions,
            __METHOD__,
            array(
                'LIMIT' => $limit + 1,
            )
        );

        $ret = array();

        $count = 0;
        foreach ($results as $res) {
            $count++;
            if ($count > $limit) {
                $this->setContinueEnumParameter('from', $res->entry_id);
                break;
            }
            $ret[] = OreDict::getArrayFromRow($res);
        }

        $this->getResult()->addValue('query', 'totalhits', $results->numRows());
        $this->getResult()->addValue('query', 'oredictentries', $ret);
    }
}
