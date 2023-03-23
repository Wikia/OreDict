<?php

use Wikimedia\ParamValidator\ParamValidator;

class OreDictAddEntryApi extends ApiBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'od');
    }

    public function getAllowedParams(): array {
        return array(
            'mod' => array(
               ParamValidator::PARAM_TYPE => 'string',
			   ParamValidator::PARAM_REQUIRED => true,
            ),
            'tag' => array(
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
            ),
            'item' => array(
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
            ),
            'params' => array(
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_DEFAULT => '',
            ),
            'token' => null,
        );
    }

    public function needsToken(): string {
        return 'csrf';
    }

    public function getTokenSalt(): string {
        return '';
    }

    public function mustBePosted(): bool {
        return true;
    }

    public function isWriteMode(): bool {
        return true;
    }

    public function getExamples(): array {
        return array(
            'api.php?action=neworedict&odmod=V&odtag=logWood&oditem=Oak Wood Log',
        );
    }

    public function execute() {
        if ( !$this->getUser()->isAllowed( 'editoredict' ) ) {
            $this->dieWithError('You do not have the permission to add OreDict entries', 'permissiondenied');
        }

        $mod = $this->getParameter('mod');
        $item = $this->getParameter('item');
        $tag = $this->getParameter('tag');
        $params = $this->getParameter('params');

        if (!OreDict::entryExists($item, $tag, $mod)) {
            $result = OreDict::addEntry($mod, $item, $tag, $this->getUser(), $params);
            $ret = array('result' => $result);
            $this->getResult()->addValue('edit', 'neworedict', $ret);
        } else {
            $this->dieWithError('Entry already exists', 'entryexists');
        }
    }
}
