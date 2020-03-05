<?php if (!defined ('postline/serverbot/models.php')) {

/*************************************************************************************************

            HSP Postline

            PHP community engine
            version 2009.07.09 (9.7.9)

                                Copyright (C) 2003-2009 by Alessandro Ghignola
                                Copyright (C) 2003-2009 Home Sweet Pixel software

            This program is free software; you can redistribute it and/or modify
            it under the terms of the GNU General Public License as published by
            the Free Software Foundation; either version 2 of the License, or
            (at your option) any later version.

            This program is distributed in the hope that it will be useful,
            but WITHOUT ANY WARRANTY; without even the implied warranty of
            MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
            GNU General Public License for more details.

            You should have received a copy of the GNU General Public License
            along with this program; if not, write to the Free Software
            Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA

*************************************************************************************************/

/*
 *
 *      IMPORTANT!
 *
 *      avoid launching this and other similary dependand scripts alone,
 *      to prevent potential malfunctions and unexpected security flaws:
 *      the starting point of launchable scripts is defining 'going'
 *
 */

$proceed = (defined ('going')) ? true : die ('[ postline serverbot syntax models decision tree ]');

/*
 *
 *      sub models
 *
 */

/*************************************************************************************************/

$m_repeat_response = array

        (

                '(strict) r_repeat_response' => 'r_repeat_already',
                '(default)' => 'r_repeat_response'

        );

/*************************************************************************************************/

$m_self_emotion = array

        (

                '(bored)' => 'r_self_bored',
                '(unsure)' => 'r_self_unsure',
                '(default)' => 'r_self_fine'

        );

/*************************************************************************************************/

$m_explain = array

        (

                '(strict) r_explain' => 'r_explain_already',
                '(default)' => 'r_explain'

        );

/*************************************************************************************************/

$m_check_statement = array

        (

                '(strict) r_unknown' => 'r_self_not_sure',
                '(default)' => 'r_self_sure'

        );

/*************************************************************************************************/

$m_altering_logs = array

        (

                '(bored) r_altering_logs' => 'r_altering_logs_already',
                '(default)' => 'r_altering_logs'

        );

/*************************************************************************************************/

$m_current_date = array

        (

                '(bored) r_current_date' => 'r_current_date_bored',
                '(default)' => 'r_current_date'

        );

/*************************************************************************************************/

$m_current_time = array

        (

                '(bored) r_current_time' => 'r_current_time_bored',
                '(default)' => 'r_current_time'

        );

/*************************************************************************************************/

$m_may_i = array

        (

                'admire. love.' => array (

                        'you.' => array (

                                'match' => 'r_user_love'

                        )

                ),

                'apologize.' => array (

                        'match' => 'r_user_sorry'

                )

        );

/*************************************************************************************************/

$m_am_i = array

        (

                'allowed.' => array (

                        'to.' => $m_may_i

                ),

                'annoying. bothering. pestering.' => array (

                        'match' => 'r_user_annoying'

                )

        );

/*************************************************************************************************/

$m_do_you_understand = array

        (

                'me.' => array (

                        'match' => 'r_user_understand'

                ),

                'what.' => array (

                        'i.' => array (

                                'say. type.' => array (

                                        'match' => 'r_user_understand'

                                ),

                                'tell.' => array (

                                        'you.' => array (

                                                'match' => 'r_user_understand'

                                        )

                                )

                        )

                ),

                'match' => 'r_user_understand'

        );

/*************************************************************************************************/

$m_are_you = array

        (

                'a. an. some. sort.' => array (

                        'bot. chatbot. chatterbot.' => array (

                                'match' => 'r_self_compare_bot'

                        ),

                        'boy. man. human.' => array (

                                'match' => 'r_self_compare_man'

                        ),

                        'girl. woman.' => array (

                                'match' => 'r_self_compare_woman'

                        ),

                        'cat.' => array (

                                'match' => 'r_self_compare_cat'

                        ),

                        'kitten. cub.' => array (

                                'match' => 'r_self_compare_kitten'

                        )

                ),

                'alive. lifeform. real. true.' => array (

                        'match' => 'r_self_alive'

                ),

                'alright. feeling. ok. working.' => array (

                        'match' => $m_self_emotion

                ),

                'doing. working.' => array (

                        'alright. properly. right. well.' => array (

                                'match' => 'r_self_condition'

                        )

                ),

                'getting. understanding.' => $m_do_you_understand,

                'here. there.' => array (

                        'match' => 'r_where_are_you'

                ),

                'sure.' => array (

                        'match' => $m_check_statement

                )

        );

/*************************************************************************************************/

$m_is_it = array

        (

                'possible. feasable.' => array (

                        'to.' => array (

                                'alter. change. edit. delete. erase.' => array (

                                        'logs.' => array (

                                                'match' => $m_altering_logs

                                        )

                                )

                        )

                ),

                'you.' => array (

                        'match' => 'r_self_identity'

                )

        );

/*************************************************************************************************/

$m_is = array (

                'it.' => $m_is_it,

                'altering. changing. editing. deleting. erasing.' => array (

                        'logs.' => array (

                                'possible. feasable.' => array (

                                        'match' => $m_altering_logs

                                )

                        )

                )

        );

/*************************************************************************************************/

$m_how_are_you = array

        (

                'meant. presumed. intended. supposed.' => array (

                        'to.' => array (

                                'function. work.' => array (

                                        'match' => 'r_self_method'

                                )

                        )

                ),

                'working.' => array (

                        'match' => 'r_self_method'

                ),

                'match' => 'r_self_condition'

        );

/*************************************************************************************************/

$m_how_do_we = array

        (

                'whisper.' => array (

                        'match' => 'r_howto_whisper'

                ),

                'yell.' => array (

                        'match' => 'r_howto_yell'

                )

        );

/*************************************************************************************************/

$m_how_do_you = array

        (

                'do.' => array (

                        'this. that. it.' => array (

                                'match' => $m_explain

                        ),

                        'match' => 'r_self_condition'

                ),

                'feel.' => array (

                        'match' => $m_self_emotion

                ),

                'function. work.' => array (

                        'match' => 'r_self_method'

                )

        );

/*************************************************************************************************/

$m_what_are_you = array

        (

                'doing.' => array (

                        'here.' => array (

                                'match' => 'r_self_purpose'

                        ),

                        'match' => 'r_self_actual_intention'

                ),

                'simulating.' => array (

                        'match' => 'r_self_identify'

                ),

                'talking.' => array (

                        'about.' => array (

                                'match' => $m_explain

                        )

                ),

                'match' => 'r_self_description'

        );

/*************************************************************************************************/

$m_what_did_you = array

        (

                'get.' => array (

                        'as. for.' => array (

                                'christmas. present.' => array (

                                        'match' => 'r_own_present'

                                )

                        )

                ),

                'mean.' => array (

                        'match' => $m_explain

                ),

                'say.' => array (

                        'match' => $m_repeat_response

                )

        );

/*************************************************************************************************/

$m_what_does_it = array

        (

                'mean.' => array (

                        'match' => $m_explain

                )

        );

/*************************************************************************************************/

$m_what_is_your = array

        (

                'kittens. sons. cubs.' => array (

                        'names.' => array (

                                'match' => 'r_kittens_names'

                        )

                ),

                'name.' => array (

                        'match' => 'r_self_name'

                ),

                'purpose.' => array (

                        'match' => 'r_self_purpose'

                ),

                'christmas. present.' => array (

                        'match' => 'r_own_present'

                )

        );

/*************************************************************************************************/

$m_who_are_you = array

        (

                'simulating.' => array (

                        'match' => 'r_self_identify'

                ),

                'match' => 'r_self_identity'

        );

/*************************************************************************************************/

$m_who_did_you = array

        (

                'say.' => array (

                        'you.' => array (

                                'are.' => array (

                                        'match' => 'r_self_identity'

                                ),

                                'were.' => array (

                                        'match' => 'r_self_identity'

                                )

                        )

                )

        );

/*************************************************************************************************/

$m_who_do_you = array

        (

                'simulate.' => array (

                        'r_self_identify'

                )

        );

/*************************************************************************************************/

/*
 *
 *      root contextual syntax model:
 *
 *      the model tries to address the structure of all prepositions, by enumerating all of the
 *      plausible combinations of words; it's a tree where each node may hold a 'default' value,
 *      marking a leaf (and the associated response), or an array, being an intermediate node,
 *      either built-in or referenced by symbol name; words have a single dot appended when the
 *      corresponding branch is expected to match after that word, or prepended when the
 *      corresponding branch is expected to match before that word (the dot determines the search
 *      direction of the words inside the branch); words may also have blank-separated synonyms
 *
 */

$m_root = array

        (

                /*
                 *
                 *      conjunctions (no blank-separated synonyms allowed here)
                 *
                 */

                '.and.' => 'p_split',
                '.but.' => 'p_split',

                /*
                 *
                 *      salutations
                 *
                 */

                'hi. yo. hello. meow.' => array (

                        'mary. lou. ml. server.' => array (

                                'match' => 'r_salutation'

                        ),

                        'alone' => 'r_salutation'

                ),

                /*
                 *
                 *      questions
                 *
                 */

                'am.' => array (

                        'i.' => $m_am_i

                ),

                'are.' => array (

                        'we.' => $m_am_i,
                        'you.' => $m_are_you

                ),

                'do. did.' => array (

                        'you.' => array (

                                'feel.' => $m_are_you,
                                'get. understand.' => $m_do_you_understand

                        )

                ),

                'elaborate. explain.' => array (

                        'please.' => array (

                                'match' => $m_explain

                        ),

                        'final' => $m_explain

                ),

                'in.' => array (

                        'what. which.' => array (

                                'sense. way.' => array (

                                        'match' => $m_explain

                                )

                        )

                ),

                'is.' => $m_is,

                'how.' => array (

                        'is. are.' => array (

                                'one. someone.' => array (

                                        'assumed. presumed. supposed.' => array (

                                                'to.' => $m_how_do_we

                                        )

                                ),

                                'today.' => array (

                                        'match' => 'r_self_condition'

                                ),

                                'you.' => $m_how_are_you,

                                'your.' => array (

                                        'kittens. sons. cubs.' => array (

                                                'called. named.' => array (

                                                        'match' => 'r_kittens_names'

                                                ),

                                                'match' => 'r_kittens_condition'

                                        )

                                )

                        ),

                        'can. could. may. should. would.' => array (

                                'i. we. one.' => $m_how_do_we,
                                'you.' => $m_explain

                        ),

                        'did. have.' => array (

                                'you.' => array (

                                        'call. called. name. named.' => array (

                                                'kittens. sons. cubs.' => array (

                                                        'match' => 'r_kittens_names'

                                                )

                                        )

                                )

                        ),

                        'do.' => array (

                                'i.' => $m_how_do_we,
                                'we.' => $m_how_do_we,
                                'you.' => $m_how_do_you

                        ),

                        'does.' => array (

                                'one.' => $m_how_do_we

                        ),

                        'so.' => array (

                                'final' => $m_explain

                        ),

                        'final' => $m_explain

                ),

                'lies.' => array (

                        'final' => 'r_self_wrong'

                ),

                'may. can. could.' => array (

                        'i.' => $m_may_i,

                        'logs.' => array (

                                'be.' => array (

                                        'altered. changed. edited. modified. corrected. screwed.'
                                        => array (

                                                'match' => $m_altering_logs

                                        )

                                )

                        )

                ),

                'no. nope. noes.' => array (

                        'i. you. he. she. it. we. they.' => array (

                                'am. are. is. was. were. can. could. will. would. should. ' .
                                'might. have. had. do. did.' => array (

                                        'not.' => array (

                                                'final' => 'r_user_denies'

                                        )

                                )

                        ),

                        'final' => 'r_user_denies'

                ),

                'really. truly.' => array (

                        'final' => $m_check_statement

                ),

                'repeat.' => array (

                        'please.' => array (

                                'match' => $m_repeat_response

                        ),

                        'alone' => $m_repeat_response

                ),

                'what.' => array (

                        'day. date.' => array (

                                'is.' => array (

                                        'it. now. this. today.' => array (

                                                'match' => $m_current_date

                                        )

                                )

                        ),

                        'do. did.' => array (

                                'you.' => $m_what_did_you

                        ),

                        'does.' => array (

                                'it. that. this.' => $m_what_does_it

                        ),

                        'is. was. are. were.' => array (

                                'a. an. the.' => array (

                                        'answer.' => array (

                                                'to.' => array (

                                                        'life. universe. everything.' => array (

                                                                'match' => 'r_final_answer'

                                                        )

                                                )

                                        ),

                                        'time.' => array (

                                                'match' => $m_current_time

                                        ),

                                        'match' => 'r_what_is_lookup'

                                ),

                                'it. he. she. that. them. they. this. these. those.' => array (

                                        'match' => 'r_what_is_something'

                                ),

                                "today. today's." => array (

                                        'match' => $m_current_date

                                ),

                                'unpossible.' => array (

                                        'match' => $m_altering_logs

                                ),

                                'you.' => $m_what_are_you,
                                'your.' => $m_what_is_your,

                                'match' => 'r_what_is_lookup'

                        ),

                        'it. he. she. that. them. they. this. these. those.' => array (

                                'is. are. was. were.' => array (

                                        'match' => 'r_what_is_something'

                                )

                        ),

                        'time.' => array (

                                'is.' => array (

                                        'it. now.' => array (

                                                'match' => $m_current_time

                                        )

                                )

                        ),

                        'final' => $m_explain   // what, so what?...

                ),

                'where.' => array (

                        'is. are. was. were.' => array (

                                'a. an. the.' => array (

                                        'match' => 'r_where_is_lookup'

                                ),

                                'it. he. she. that. them. they. this. these. those.' => array (

                                        'match' => 'r_where_is_something'

                                ),

                                'my.' => array (

                                        'pants.' => array (

                                                'match' => 'r_where_are_my_pants'

                                        )

                                ),

                                'you. mary. lou.' => array (

                                        'match' => 'r_where_are_you'

                                ),

                                'match' => 'r_where_is_lookup'

                        ),

                        'it. he. she. that. them. they. this. these. those.' => array (

                                'is. are. was. were.' => array (

                                        'match' => 'r_where_is_something'

                                )

                        )

                ),

                'which.' => array (

                        'is.' => array (

                                'the.' => array (

                                        'answer.' => array (

                                                'to.' => array (

                                                        'life. universe. everything.' => array (

                                                                'match' => 'r_final_answer'

                                                        )

                                                )

                                        )

                                )

                        )

                ),

                'who.' => array (

                        'did.' => array (

                                'you.' => $m_who_did_you

                        ),

                        'do.' => array (

                                'you.' => $m_who_do_you

                        ),

                        'is. are. was. were.' => array (

                                'a. an. the.' => array (

                                        'match' => 'r_what_is_lookup'

                                ),

                                'it. he. she. that. them. they. this. these. those.' => array (

                                        'match' => 'r_what_is_something'

                                ),

                                'mary.' => array (

                                        'lou.' => array (

                                                'match' => 'r_model_identity'

                                        )

                                ),

                                'you.' => $m_who_are_you,

                                'match' => 'r_what_is_lookup'

                        ),

                        'it. he. she. that. them. they. this. these. those.' => array (

                                'is. are. was. were.' => array (

                                        'match' => 'r_what_is_something'

                                )

                        )

                ),

                'why.' => array (

                        'this. that. it.' => array (

                                'final' => $m_explain

                        ),

                        'final' => $m_explain

                ),

                'would.' => array (

                        'you.' => array (

                                'claim. define. feel. state.' => array (

                                        'you. yourself.' => $m_are_you

                                )

                        )

                ),

                'yes. yeah. yep. yea.' => array (

                        'i. you. he. she. it. we. they.' => array (

                                'am. are. is. was. were. can. could. will. would. should. ' .
                                'might. have. had. do. did.' => array (

                                        'final' => 'r_user_confirms'

                                )

                        ),

                        'final' => 'r_user_confirms'

                ),

                /*
                 *
                 *      reversed questions,
                 *      e.g. 'who [did] you [say you] are?' = ('.are' => '.you' => '.who')
                 *
                 */

                '.are .were' => array (

                        '.you' => array (

                                '.who' => array (

                                        'match' => 'r_self_identity'

                                )

                        )

                ),

                '.what .who' => array (

                        '.doing .simulating' => array (

                                'match' => 'r_self_purpose'

                        ),

                        '.of' => array (

                                '.presence' => array (

                                        'match' => 'r_self_description'

                                )

                        )

                ),

                /*
                 *
                 *      miscellaneous sentences and user observations
                 *
                 */

                'happy. merry. good.' => array (

                        'birthday.' => array (

                                'match' => 'r_happy_birthday'

                        ),

                        'christmas. xmas.' => array (

                                'match' => 'r_merry_christmas'

                        ),

                        'year.' => array (

                                'match' => 'r_happy_new_year'

                        )

                ),

                'i.' => array (

                        'am.' => array (

                                'sorry.' => array (

                                        'match' => 'r_user_sorry'

                                )

                        ),

                        'apologize.' => array (

                                'match' => 'r_user_sorry'

                        ),

                        'like. love.' => array (

                                'you.' => array (

                                        'match' => 'r_user_love'

                                )

                        )

                ),

                'it. he. she. they. we.' => array (

                        'is. are. was. were.' => array (

                                'not.' => array (

                                        'match' => 'r_user_denies'

                                )

                        )

                ),

                'right. correct. ok.' => array (

                        'final' => 'r_self_correct'

                ),

                'sorry.' => array (

                        'mary. lou.' => array (

                                'match' => 'r_user_sorry'

                        ),

                        'alone' => 'r_user_sorry'

                ),

                'thank.' => array (

                        'you.' => array (

                                'match' => 'r_user_thanks'

                        )

                ),

                'thanks.' => array (

                        'final' => 'r_user_thanks'

                ),

                'that.' => array (

                        'is.' => array (

                                'bullshit. incorrect. lying. lie. wrong.' => array (

                                        'match' => 'r_self_wrong'

                                ),

                                'correct. right.' => array (

                                        'match' => 'r_self_correct'

                                )

                        )

                ),

                'very.' => array (

                        'much. indeed.' => array (

                                'match' => 'r_user_thanks'

                        )

                ),

                'you.' => array (

                        'are.' => array (

                                'correct. right.' => array (

                                        'match' => 'r_self_correct'

                                ),

                                'incorrect. lying. wrong.' => array (

                                        'match' => 'r_self_wrong'

                                )

                        )

                )

        );

/*************************************************************************************************/

define ('postline/serverbot/models.php', true); } ?>
