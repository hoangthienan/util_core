<?php

namespace go1\util\lo\explore;

use go1\util\customer\CustomerEsSchema;
use go1\util\enrolment\EnrolmentStatuses;
use go1\util\es\Schema;

class LoExploreSchema
{
    const BODY = [
        'mappings' => self::MAPPING,
        'settings' => self::SETTINGS,
    ];

    const SETTINGS = [
        'analysis' => [
            'normalizer' => [
                'lowercase' => [
                    'type' => 'custom',
                    'filter' => ['lowercase']
                ]
            ]
        ]
    ];

    const MAPPING = [
        Schema::O_LO                => self::LO_MAPPING,
        Schema::O_GROUP             => self::GROUP_MAPPING,
        Schema::O_ENROLMENT         => self::ENROLMENT_MAPPING,
        Schema::O_GROUP_ITEM        => self::GROUP_ITEM_MAPPING,
        CustomerEsSchema::O_ACCOUNT => self::ACCOUNT_MAPPING,
        CustomerEsSchema::O_PORTAL  => self::PORTAL_MAPPING,
    ];

    const LO_MAPPING = [
        '_routing'   => ['required' => true],
        'properties' => [
            'id'              => ['type' => Schema::T_KEYWORD],
            'type'            => ['type' => Schema::T_KEYWORD],
            'origin_id'       => ['type' => Schema::T_INT],
            'private'         => ['type' => Schema::T_INT],
            'published'       => ['type' => Schema::T_INT],
            'marketplace'     => ['type' => Schema::T_INT],
            'sharing'         => ['type' => Schema::T_SHORT],
            'portal_id'       => ['type' => Schema::T_INT],
            'portal_name'     => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
            'language'        => ['type' => Schema::T_KEYWORD],
            'locale'          => ['type' => Schema::T_KEYWORD],
            'title'           => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
            'description'     => ['type' => Schema::T_TEXT],
            'summary'         => ['type' => Schema::T_TEXT],
            'tags'            => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED_AND_NORMALIZED,
            'custom_tags'     => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED_AND_NORMALIZED,
            'topics'          => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
            'pricing'         => [
                'properties' => [
                    'currency' => ['type' => Schema::T_KEYWORD],
                    'price'    => ['type' => Schema::T_DOUBLE],
                    'total'    => ['type' => Schema::T_DOUBLE],
                ],
            ],
            'duration'        => ['type' => Schema::T_INT], # Duration in minute
            'assessors'       => ['type' => Schema::T_INT],
            'collections'     => ['type' => Schema::T_INT],
            'group'           => [
                'properties' => [
                    'content' => ['type' => Schema::T_INT],
                ],
            ],
            'allow_enrolment' => ['type' => Schema::T_INT],
            'totalEnrolment'  => ['type' => Schema::T_INT],
            'created'         => ['type' => Schema::T_DATE],
            'updated'         => ['type' => Schema::T_DATE],
            'authors'         => [
                'type'       => Schema::T_NESTED,
                'properties' => [
                    'id'         => ['type' => Schema::T_KEYWORD],
                    'name'       => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                    'first_name' => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                    'last_name'  => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                    'avatar'     => ['type' => Schema::T_TEXT],
                ],
            ],
            'data'            => [
                'properties' => [
                    'single_li' => ['type' => Schema::T_SHORT],
                    'source_id' => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                ],
            ],
            'locations'       => [
                'type'       => Schema::T_NESTED,
                'properties' => [
                    'id'                       => ['type' => Schema::T_KEYWORD],
                    'country'                  => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                    'country_name'             => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                    'administrative_area'      => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                    'administrative_area_name' => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                    'locality'                 => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                    'thoroughfare'             => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                    'coordinate'               => ['type' => Schema::T_GEO_POINT],
                ],
            ],
            'attributes'    => [
                'properties' => [
                    'learning_outcomes'       => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                    'assessable'              => ['type' => Schema::T_INT],
                    'mobile_optimised'        => ['type' => Schema::T_INT],
                    'wcag'                    => ['type' => Schema::T_INT],
                    'internal_qa_rating'      => ['type' => Schema::T_INT],
                    'download_speed'          => ['type' => Schema::T_INT],
                    'audio_visual_design'     => ['type' => Schema::T_INT],
                    'presentation_of_content' => ['type' => Schema::T_INT],
                    'structure_navigation'    => ['type' => Schema::T_INT],
                    'featured_status'         => ['type' => Schema::T_INT],
                    'featured_locale'         => ['type' => Schema::T_KEYWORD],
                    'featured_timestamp'      => ['type' => Schema::T_INT],
                    'entry_level' => [
                        'properties'    => [
                            'value'     => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                            'key'       => ['type' => Schema::T_KEYWORD],
                        ],
                    ],
                    'region_restrictions' => [
                        'type'          => Schema::T_NESTED,
                        'properties'    => [
                            'value'     => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                            'key'       => ['type' => Schema::T_KEYWORD],
                        ],
                    ],
                    'topics' => [
                        'type'          => Schema::T_NESTED,
                        'properties'    => [
                            'value'     => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                            'key'       => ['type' => Schema::T_KEYWORD],
                        ],
                    ],
                    'locale' => [
                        'type'          => Schema::T_NESTED,
                        'properties'    => [
                            'value'     => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                            'key'       => ['type' => Schema::T_KEYWORD],
                        ],
                    ],
                    'region_relevance' => [
                        'type'          => Schema::T_NESTED,
                        'properties'    => [
                            'value'     => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                            'key'       => ['type' => Schema::T_KEYWORD],
                        ],
                    ],
                    'industry' => [
                        'type'          => Schema::T_NESTED,
                        'properties'    => [
                            'value'     => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                            'key'       => ['type' => Schema::T_KEYWORD],
                        ],
                    ],
                    'roles' => [
                        'type'          => Schema::T_NESTED,
                        'properties'    => [
                            'value'     => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                            'key'       => ['type' => Schema::T_KEYWORD],
                        ],
                    ],
                    'skills' => [
                        'type'          => Schema::T_NESTED,
                        'properties'    => [
                            'value'     => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                            'key'       => ['type' => Schema::T_KEYWORD],
                        ],
                    ],
                    /** @see https://github.com/go1com/util_core/blob/master/lo/LoAttributes.php#L20 */
                    'provider' => ['type' => Schema::T_KEYWORD],
                ],
            ],
            'events'         => [
                'type'       => Schema::T_NESTED,
                'properties' => [
                    'id'                       => ['type' => Schema::T_KEYWORD],
                    'lo_id'                    => ['type' => Schema::T_INT],
                    'title'                    => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                    'start'                    => ['type' => Schema::T_DATE],
                    'end'                      => ['type' => Schema::T_DATE],
                    'timezone'                 => ['type' => Schema::T_KEYWORD],
                    'seats'                    => ['type' => Schema::T_INT], # Or attendee_limit
                    'available_seats'          => ['type' => Schema::T_INT],
                    'country'                  => ['type' => Schema::T_KEYWORD],
                    'country_name'             => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                    'administrative_area'      => ['type' => Schema::T_KEYWORD],
                    'administrative_area_name' => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                    'locality'                 => ['type' => Schema::T_KEYWORD],
                    'location_name'            => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                    'dependent_locality'       => ['type' => Schema::T_KEYWORD],
                    'thoroughfare'             => ['type' => Schema::T_KEYWORD] + Schema::ANALYZED,
                    'instructor_ids'           => ['type' => Schema::T_INT],
                    'coordinate'               => ['type' => Schema::T_GEO_POINT],
                ],
            ],
            'vote'            => [
                'properties' => [
                    'percent' => ['type' => Schema::T_INT],
                    'rank'    => ['type' => Schema::T_INT],
                    'like'    => ['type' => Schema::T_INT],
                    'dislike' => ['type' => Schema::T_INT],
                ],
            ],
            'policy'          => [
                'type'       => Schema::T_NESTED,
                'properties' => [
                    'id'        => ['type' => Schema::T_KEYWORD],
                    'realm'     => ['type' => Schema::T_SHORT],
                    'portal_id' => ['type' => Schema::T_INT],
                    'group_id'  => ['type' => Schema::T_INT],
                    'user_id'   => ['type' => Schema::T_INT],
                ],
            ],
            'metadata'        => [
                'properties' => [
                    'portal_id'  => ['type' => Schema::T_INT],
                    'updated_at' => ['type' => Schema::T_INT],
                ],
            ],
            'product_ids' => ['type' => Schema::T_INT], # @see go1-core/content-subscription-index
            'decommissioned_at' => ['type' => Schema::T_DATE],
            'removed_at' => ['type' => Schema::T_DATE],
        ],
    ];

    const ENROLMENT_MAPPING = [
        '_routing'   => ['required' => true],
        'properties' => [
            'id'         => ['type' => Schema::T_KEYWORD],
            'type'       => ['type' => Schema::T_KEYWORD],
            'account_id' => ['type' => Schema::T_INT],
            'status'     => ['type' => Schema::T_SHORT],
            'pass'       => ['type' => Schema::T_INT],
            'portal_id'  => ['type' => Schema::T_INT],
            'metadata'   => [
                'properties' => [
                    'portal_id'  => ['type' => Schema::T_INT],
                    'updated_at' => ['type' => Schema::T_INT],
                ],
            ],
        ],
    ];

    const GROUP_ITEM_MAPPING = [
        '_routing'   => ['required' => true],
        'properties' => [
            'id'          => ['type' => Schema::T_KEYWORD],
            'group_id'    => ['type' => Schema::T_INT],
            'entity_type' => ['type' => Schema::T_KEYWORD],
            'entity_id'   => ['type' => Schema::T_INT],
            'portal_id'   => ['type' => Schema::T_INT],
            'metadata'    => [
                'properties' => [
                    'portal_id'  => ['type' => Schema::T_INT],
                    'updated_at' => ['type' => Schema::T_INT],
                ],
            ],
        ],
    ];

    const ACCOUNT_MAPPING = [
        '_routing'   => ['required' => true],
        'properties' => [
            'id'        => ['type' => Schema::T_KEYWORD],
            'groups'    => ['type' => Schema::T_INT],
            'enrolment' => [
                'properties' => [
                    'assigned'                   => ['type' => Schema::T_KEYWORD],
                    'not_started'                => ['type' => Schema::T_KEYWORD],
                    'in_progress'                => ['type' => Schema::T_KEYWORD],
                    'last_completed'             => ['type' => Schema::T_KEYWORD],
                    EnrolmentStatuses::COMPLETED => ['type' => Schema::T_KEYWORD],
                    EnrolmentStatuses::EXPIRED   => ['type' => Schema::T_KEYWORD],
                    'all'                        => ['type' => Schema::T_KEYWORD],
                ],
            ],
            'metadata'  => [
                'properties' => [
                    'portal_id'  => ['type' => Schema::T_INT],
                    'updated_at' => ['type' => Schema::T_INT],
                ],
            ],
        ],
    ];

    const PORTAL_MAPPING = [
        '_routing'   => ['required' => true],
        'properties' => [
            'id'                 => ['type' => Schema::T_KEYWORD],
            'groups'             => ['type' => Schema::T_INT],
            'groups_v1'          => ['type' => Schema::T_INT], # List of group(version 1) that shared to portal via group policy.
            'selected_groups_v1' => ['type' => Schema::T_INT], # List of group(version 1) that selected via portal content selection.
            'metadata'           => [
                'properties' => [
                    'portal_id'  => ['type' => Schema::T_INT],
                    'updated_at' => ['type' => Schema::T_INT],
                ],
            ],
        ],
    ];

    const GROUP_MAPPING = [
        '_routing'   => ['required' => true],
        'properties' => [
            'id'               => ['type' => Schema::T_KEYWORD],
            'assigned_content' => ['type' => Schema::T_KEYWORD],
            'metadata'         => [
                'properties' => [
                    'portal_id'  => ['type' => Schema::T_INT],
                    'updated_at' => ['type' => Schema::T_INT],
                ],
            ],
        ],
    ];
}
