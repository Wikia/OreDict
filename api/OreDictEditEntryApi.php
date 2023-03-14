<?php

use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\NumericDef;

class OreDictEditEntryApi extends ApiBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'od');
    }

    public function getAllowedParams(): array {
        return array(
            'token' => null,
            'mod' => [
                ParamValidator::PARAM_TYPE => 'string',
            ],
            'tag' => [
				ParamValidator::PARAM_TYPE => 'string',
            ],
            'item' => [
				ParamValidator::PARAM_TYPE => 'string',
            ],
            'params' => [
				ParamValidator::PARAM_TYPE => 'string',
            ],
            'id' => [
				ParamValidator::PARAM_TYPE => 'integer',
				NumericDef::PARAM_MIN => 1,
				ParamValidator::PARAM_REQUIRED => true,
            ],
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
            'api.php?action=editoredict&odmod=NEWMOD&odid=1',
        );
    }

    public function execute() {
		if ( !$this->getUser()->isAllowed( 'editoredict' ) ) {
            $this->dieWithError('You do not have the permission to add OreDict entries', 'permissiondenied');
        }

        $id = $this->getParameter('id');

        if (!OreDict::checkExistsByID($id)) {
            $this->dieWithError("Entry $id does not exist", 'entrynotexist');
        }

        $mod = $this->getParameter('mod');
        $item = $this->getParameter('item');
        $tag = $this->getParameter('tag');
        $params = $this->getParameter('params');

        $update = array(
            'mod_name' => $mod,
            'item_name' => $item,
            'tag_name' => $tag,
            'grid_params' => $params,
        );

        $result = OreDict::editEntry($update, $id, $this->getUser());
        $ret = array();
        switch ($result) {
            case 0: {
                $ret = array($id => true);
                break;
            }
            case 1: {
                $this->dieWithError("Failed to edit $id in the database", 'dbfail');
            }
            case 2: {
                $this->dieWithError("There was no change made for entry $id", 'nodiff');
            }
        }

        $this->getResult()->addValue('edit', 'editoredict', $ret);
    }
}
