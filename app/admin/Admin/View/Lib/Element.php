<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\View\Lib;

use Toknot\Boot\Tookit;
use Toknot\Share\View\Tag;

/**
 * Form
 *
 */
class Element {

    /**
     * create form
     * 
     * $data like below:
     * [ action => url, type => form, 
     *   input => [ name1 => [ type => text, value => '', label =>''],
     *              name2 => [ type => password, value =>'', label =>''],
     *              [ type => 'submit' ]
     *            ]
     * ]
     * 
     * @param Toknot\Share\View\TagBulid $parentNode
     * @param array $data
     * @return Toknot\Share\View\TagBulid
     */
    public static function postForm($data) {
        $enctype = Tookit::coalesce($data, 'type', 'form');

        $inputs = [];
        $select = [];
        foreach ($data['input'] as $name => $input) {
            $type = Tookit::coalesce($input, 'type', 'text');
            if ($type == 'checkbox' || $type == 'radio') {
                $select[$name] = isset($select[$name]) ? $select[$name] + 1 : 0;
                $select[$name] > 0 && ($name .= $select[$name]);
            }
            Tookit::coalesce($input, 'id', $name);
            $label = Tookit::coalesce($input, 'label', '');
            Tookit::coalesce($input, 'placeholder', $label);
            if ($input['type'] == 'submit') {
                Tookit::coalesce($input, 'class', 'pure-button pure-button-primary');
            } elseif ($input['type'] == 'button') {
                Tookit::coalesce($input, 'class', 'pure-button');
            }
            $inputs[$name] = $input;
        }
        $form = Tag::form()
                ->addClass('pure-form pure-form-stacked')
                ->setMethod()
                ->setAction($data['action'])
                ->setType($data[$enctype])
                ->inputs($inputs);
        return $form;
    }

    public static function table($data) {
        $rightTable = Tag::table(['class' => 'pure-table']);
        if (isset($data['title'])) {
            $rightTableThead = $this->thead();
            $rightTable->push($rightTableThead);
            $tr = $this->tr();
            $rightTableThead->push($tr);
            foreach ($data['title'] as $tname) {
                $td = $this->td()->pushText($tname);
                $tr->push($td);
            }
        }
        if (isset($data['tbody'])) {
            $rightTableBody = $this->tbody();
            $rightTable->push($rightTableBody);
            foreach ($data['tbody'] as $line) {
                $bodyTr = $this->tr();
                $rightTableBody->push($bodyTr);
                foreach ($line as $t => $column) {
                    if ($t == 'input') {
                        $td = $this->td();
                        $bodyTr->push($td);
                        $input = Tag::input($column);
                        $td->push($input);
                    } else {
                        $td = $this->td()->pushText($column);
                        $bodyTr->push($td);
                    }
                }
            }
        }
        return $rightTable;
    }

}
