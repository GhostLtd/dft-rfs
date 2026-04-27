-- The "is_name_to_country_only" is used to distinguish official names
-- from legacy names.
--
-- e.g. we clearly want to resolve "Turkey" to "TR", but as it's not the
--      official name, so we would never resolve "TR" to "Turkey" and as
--      such mark the row with a 1.
CREATE OR REPLACE VIEW view_countries AS (
    SELECT 'AF' AS code, 'Afghanistan' AS name, 0 AS is_name_to_country_only
    UNION ALL SELECT 'AX', 'Åland Islands', 0
    UNION ALL SELECT 'AL', 'Albania', 0
    UNION ALL SELECT 'DZ', 'Algeria', 0
    UNION ALL SELECT 'AS', 'American Samoa', 0
    UNION ALL SELECT 'AD', 'Andorra', 0
    UNION ALL SELECT 'AO', 'Angola', 0
    UNION ALL SELECT 'AI', 'Anguilla', 0
    UNION ALL SELECT 'AQ', 'Antarctica', 0
    UNION ALL SELECT 'AG', 'Antigua & Barbuda', 0
    UNION ALL SELECT 'AR', 'Argentina', 0
    UNION ALL SELECT 'AM', 'Armenia', 0
    UNION ALL SELECT 'AW', 'Aruba', 0
    UNION ALL SELECT 'AU', 'Australia', 0
    UNION ALL SELECT 'AT', 'Austria', 0
    UNION ALL SELECT 'AZ', 'Azerbaijan', 0
    UNION ALL SELECT 'BS', 'Bahamas', 0
    UNION ALL SELECT 'BH', 'Bahrain', 0
    UNION ALL SELECT 'BD', 'Bangladesh', 0
    UNION ALL SELECT 'BB', 'Barbados', 0
    UNION ALL SELECT 'BY', 'Belarus', 0
    UNION ALL SELECT 'BE', 'Belgium', 0
    UNION ALL SELECT 'BZ', 'Belize', 0
    UNION ALL SELECT 'BJ', 'Benin', 0
    UNION ALL SELECT 'BM', 'Bermuda', 0
    UNION ALL SELECT 'BT', 'Bhutan', 0
    UNION ALL SELECT 'BO', 'Bolivia', 0
    UNION ALL SELECT 'BA', 'Bosnia & Herzegovina', 0
    UNION ALL SELECT 'BW', 'Botswana', 0
    UNION ALL SELECT 'BV', 'Bouvet Island', 0
    UNION ALL SELECT 'BR', 'Brazil', 0
    UNION ALL SELECT 'IO', 'British Indian Ocean Territory', 0
    UNION ALL SELECT 'VG', 'British Virgin Islands', 0
    UNION ALL SELECT 'BN', 'Brunei', 0
    UNION ALL SELECT 'BG', 'Bulgaria', 0
    UNION ALL SELECT 'BF', 'Burkina Faso', 0
    UNION ALL SELECT 'BI', 'Burundi', 0
    UNION ALL SELECT 'KH', 'Cambodia', 0
    UNION ALL SELECT 'CM', 'Cameroon', 0
    UNION ALL SELECT 'CA', 'Canada', 0
    UNION ALL SELECT 'CV', 'Cape Verde', 0
    UNION ALL SELECT 'BQ', 'Caribbean Netherlands', 0
    UNION ALL SELECT 'KY', 'Cayman Islands', 0
    UNION ALL SELECT 'CF', 'Central African Republic', 0
    UNION ALL SELECT 'TD', 'Chad', 0
    UNION ALL SELECT 'CL', 'Chile', 0
    UNION ALL SELECT 'CN', 'China', 0
    UNION ALL SELECT 'CX', 'Christmas Island', 0
    UNION ALL SELECT 'CC', 'Cocos (Keeling) Islands', 0
    UNION ALL SELECT 'CO', 'Colombia', 0
    UNION ALL SELECT 'KM', 'Comoros', 0
    UNION ALL SELECT 'CG', 'Congo - Brazzaville', 0
    UNION ALL SELECT 'CD', 'Congo - Kinshasa', 0
    UNION ALL SELECT 'CK', 'Cook Islands', 0
    UNION ALL SELECT 'CR', 'Costa Rica', 0
    UNION ALL SELECT 'CI', 'Côte d’Ivoire', 0
    UNION ALL SELECT 'HR', 'Croatia', 0
    UNION ALL SELECT 'CU', 'Cuba', 0
    UNION ALL SELECT 'CW', 'Curaçao', 0
    UNION ALL SELECT 'CY', 'Cyprus', 0
    UNION ALL SELECT 'CZ', 'Czechia', 0
    UNION ALL SELECT 'CZ', 'Czech Republic', 1
    UNION ALL SELECT 'DK', 'Denmark', 0
    UNION ALL SELECT 'DJ', 'Djibouti', 0
    UNION ALL SELECT 'DM', 'Dominica', 0
    UNION ALL SELECT 'DO', 'Dominican Republic', 0
    UNION ALL SELECT 'EC', 'Ecuador', 0
    UNION ALL SELECT 'EG', 'Egypt', 0
    UNION ALL SELECT 'SV', 'El Salvador', 0
    UNION ALL SELECT 'GQ', 'Equatorial Guinea', 0
    UNION ALL SELECT 'ER', 'Eritrea', 0
    UNION ALL SELECT 'EE', 'Estonia', 0
    UNION ALL SELECT 'SZ', 'Eswatini', 0
    UNION ALL SELECT 'ET', 'Ethiopia', 0
    UNION ALL SELECT 'FK', 'Falkland Islands', 0
    UNION ALL SELECT 'FO', 'Faroe Islands', 0
    UNION ALL SELECT 'FJ', 'Fiji', 0
    UNION ALL SELECT 'FI', 'Finland', 0
    UNION ALL SELECT 'FR', 'France', 0
    UNION ALL SELECT 'GF', 'French Guiana', 0
    UNION ALL SELECT 'PF', 'French Polynesia', 0
    UNION ALL SELECT 'TF', 'French Southern Territories', 0
    UNION ALL SELECT 'GA', 'Gabon', 0
    UNION ALL SELECT 'GM', 'Gambia', 0
    UNION ALL SELECT 'GE', 'Georgia', 0
    UNION ALL SELECT 'DE', 'Germany', 0
    UNION ALL SELECT 'GH', 'Ghana', 0
    UNION ALL SELECT 'GI', 'Gibraltar', 0
    UNION ALL SELECT 'GR', 'Greece', 0
    UNION ALL SELECT 'GL', 'Greenland', 0
    UNION ALL SELECT 'GD', 'Grenada', 0
    UNION ALL SELECT 'GP', 'Guadeloupe', 0
    UNION ALL SELECT 'GU', 'Guam', 0
    UNION ALL SELECT 'GT', 'Guatemala', 0
    UNION ALL SELECT 'GG', 'Guernsey', 0
    UNION ALL SELECT 'GN', 'Guinea', 0
    UNION ALL SELECT 'GW', 'Guinea-Bissau', 0
    UNION ALL SELECT 'GY', 'Guyana', 0
    UNION ALL SELECT 'HT', 'Haiti', 0
    UNION ALL SELECT 'HM', 'Heard & McDonald Islands', 0
    UNION ALL SELECT 'HN', 'Honduras', 0
    UNION ALL SELECT 'HK', 'Hong Kong SAR China', 0
    UNION ALL SELECT 'HU', 'Hungary', 0
    UNION ALL SELECT 'IS', 'Iceland', 0
    UNION ALL SELECT 'IN', 'India', 0
    UNION ALL SELECT 'ID', 'Indonesia', 0
    UNION ALL SELECT 'IR', 'Iran', 0
    UNION ALL SELECT 'IQ', 'Iraq', 0
    UNION ALL SELECT 'IE', 'Ireland', 0
    UNION ALL SELECT 'IM', 'Isle of Man', 0
    UNION ALL SELECT 'IL', 'Israel', 0
    UNION ALL SELECT 'IT', 'Italy', 0
    UNION ALL SELECT 'JM', 'Jamaica', 0
    UNION ALL SELECT 'JP', 'Japan', 0
    UNION ALL SELECT 'JE', 'Jersey', 0
    UNION ALL SELECT 'JO', 'Jordan', 0
    UNION ALL SELECT 'KZ', 'Kazakhstan', 0
    UNION ALL SELECT 'KE', 'Kenya', 0
    UNION ALL SELECT 'KI', 'Kiribati', 0
    UNION ALL SELECT 'KW', 'Kuwait', 0
    UNION ALL SELECT 'KG', 'Kyrgyzstan', 0
    UNION ALL SELECT 'LA', 'Laos', 0
    UNION ALL SELECT 'LV', 'Latvia', 0
    UNION ALL SELECT 'LB', 'Lebanon', 0
    UNION ALL SELECT 'LS', 'Lesotho', 0
    UNION ALL SELECT 'LR', 'Liberia', 0
    UNION ALL SELECT 'LY', 'Libya', 0
    UNION ALL SELECT 'LI', 'Liechtenstein', 0
    UNION ALL SELECT 'LT', 'Lithuania', 0
    UNION ALL SELECT 'LU', 'Luxembourg', 0
    UNION ALL SELECT 'MO', 'Macao SAR China', 0
    UNION ALL SELECT 'MG', 'Madagascar', 0
    UNION ALL SELECT 'MW', 'Malawi', 0
    UNION ALL SELECT 'MY', 'Malaysia', 0
    UNION ALL SELECT 'MV', 'Maldives', 0
    UNION ALL SELECT 'ML', 'Mali', 0
    UNION ALL SELECT 'MT', 'Malta', 0
    UNION ALL SELECT 'MH', 'Marshall Islands', 0
    UNION ALL SELECT 'MQ', 'Martinique', 0
    UNION ALL SELECT 'MR', 'Mauritania', 0
    UNION ALL SELECT 'MU', 'Mauritius', 0
    UNION ALL SELECT 'YT', 'Mayotte', 0
    UNION ALL SELECT 'MX', 'Mexico', 0
    UNION ALL SELECT 'FM', 'Micronesia', 0
    UNION ALL SELECT 'MD', 'Moldova', 0
    UNION ALL SELECT 'MC', 'Monaco', 0
    UNION ALL SELECT 'MN', 'Mongolia', 0
    UNION ALL SELECT 'ME', 'Montenegro', 0
    UNION ALL SELECT 'MS', 'Montserrat', 0
    UNION ALL SELECT 'MA', 'Morocco', 0
    UNION ALL SELECT 'MZ', 'Mozambique', 0
    UNION ALL SELECT 'MM', 'Myanmar (Burma)', 0
    UNION ALL SELECT 'NA', 'Namibia', 0
    UNION ALL SELECT 'NR', 'Nauru', 0
    UNION ALL SELECT 'NP', 'Nepal', 0
    UNION ALL SELECT 'NL', 'Netherlands', 0
    UNION ALL SELECT 'NC', 'New Caledonia', 0
    UNION ALL SELECT 'NZ', 'New Zealand', 0
    UNION ALL SELECT 'NI', 'Nicaragua', 0
    UNION ALL SELECT 'NE', 'Niger', 0
    UNION ALL SELECT 'NG', 'Nigeria', 0
    UNION ALL SELECT 'NU', 'Niue', 0
    UNION ALL SELECT 'NF', 'Norfolk Island', 0
    UNION ALL SELECT 'KP', 'North Korea', 0
    UNION ALL SELECT 'MK', 'North Macedonia', 0
    UNION ALL SELECT 'MP', 'Northern Mariana Islands', 0
    UNION ALL SELECT 'NO', 'Norway', 0
    UNION ALL SELECT 'OM', 'Oman', 0
    UNION ALL SELECT 'PK', 'Pakistan', 0
    UNION ALL SELECT 'PW', 'Palau', 0
    UNION ALL SELECT 'PS', 'Palestinian Territories', 0
    UNION ALL SELECT 'PA', 'Panama', 0
    UNION ALL SELECT 'PG', 'Papua New Guinea', 0
    UNION ALL SELECT 'PY', 'Paraguay', 0
    UNION ALL SELECT 'PE', 'Peru', 0
    UNION ALL SELECT 'PH', 'Philippines', 0
    UNION ALL SELECT 'PN', 'Pitcairn Islands', 0
    UNION ALL SELECT 'PL', 'Poland', 0
    UNION ALL SELECT 'PT', 'Portugal', 0
    UNION ALL SELECT 'PR', 'Puerto Rico', 0
    UNION ALL SELECT 'QA', 'Qatar', 0
    UNION ALL SELECT 'RE', 'Réunion', 0
    UNION ALL SELECT 'RO', 'Romania', 0
    UNION ALL SELECT 'RU', 'Russia', 0
    UNION ALL SELECT 'RW', 'Rwanda', 0
    UNION ALL SELECT 'WS', 'Samoa', 0
    UNION ALL SELECT 'SM', 'San Marino', 0
    UNION ALL SELECT 'ST', 'São Tomé & Príncipe', 0
    UNION ALL SELECT 'SA', 'Saudi Arabia', 0
    UNION ALL SELECT 'SN', 'Senegal', 0
    UNION ALL SELECT 'RS', 'Serbia', 0
    UNION ALL SELECT 'SC', 'Seychelles', 0
    UNION ALL SELECT 'SL', 'Sierra Leone', 0
    UNION ALL SELECT 'SG', 'Singapore', 0
    UNION ALL SELECT 'SX', 'Sint Maarten', 0
    UNION ALL SELECT 'SK', 'Slovakia', 0
    UNION ALL SELECT 'SI', 'Slovenia', 0
    UNION ALL SELECT 'SB', 'Solomon Islands', 0
    UNION ALL SELECT 'SO', 'Somalia', 0
    UNION ALL SELECT 'ZA', 'South Africa', 0
    UNION ALL SELECT 'GS', 'South Georgia & South Sandwich Islands', 0
    UNION ALL SELECT 'KR', 'South Korea', 0
    UNION ALL SELECT 'SS', 'South Sudan', 0
    UNION ALL SELECT 'ES', 'Spain', 0
    UNION ALL SELECT 'LK', 'Sri Lanka', 0
    UNION ALL SELECT 'BL', 'St. Barthélemy', 0
    UNION ALL SELECT 'SH', 'St. Helena', 0
    UNION ALL SELECT 'KN', 'St. Kitts & Nevis', 0
    UNION ALL SELECT 'LC', 'St. Lucia', 0
    UNION ALL SELECT 'MF', 'St. Martin', 0
    UNION ALL SELECT 'PM', 'St. Pierre & Miquelon', 0
    UNION ALL SELECT 'VC', 'St. Vincent & Grenadines', 0
    UNION ALL SELECT 'SD', 'Sudan', 0
    UNION ALL SELECT 'SR', 'Suriname', 0
    UNION ALL SELECT 'SJ', 'Svalbard & Jan Mayen', 0
    UNION ALL SELECT 'SE', 'Sweden', 0
    UNION ALL SELECT 'CH', 'Switzerland', 0
    UNION ALL SELECT 'SY', 'Syria', 0
    UNION ALL SELECT 'TW', 'Taiwan', 0
    UNION ALL SELECT 'TJ', 'Tajikistan', 0
    UNION ALL SELECT 'TZ', 'Tanzania', 0
    UNION ALL SELECT 'TH', 'Thailand', 0
    UNION ALL SELECT 'TL', 'Timor-Leste', 0
    UNION ALL SELECT 'TG', 'Togo', 0
    UNION ALL SELECT 'TK', 'Tokelau', 0
    UNION ALL SELECT 'TO', 'Tonga', 0
    UNION ALL SELECT 'TT', 'Trinidad & Tobago', 0
    UNION ALL SELECT 'TN', 'Tunisia', 0
    UNION ALL SELECT 'TR', 'Türkiye', 0
    UNION ALL SELECT 'TR', 'Turkey', 1
    UNION ALL SELECT 'TM', 'Turkmenistan', 0
    UNION ALL SELECT 'TC', 'Turks & Caicos Islands', 0
    UNION ALL SELECT 'TV', 'Tuvalu', 0
    UNION ALL SELECT 'UM', 'U.S. Outlying Islands', 0
    UNION ALL SELECT 'VI', 'U.S. Virgin Islands', 0
    UNION ALL SELECT 'UG', 'Uganda', 0
    UNION ALL SELECT 'UA', 'Ukraine', 0
    UNION ALL SELECT 'AE', 'United Arab Emirates', 0
    UNION ALL SELECT 'GB', 'United Kingdom', 0
    UNION ALL SELECT 'US', 'United States', 0
    UNION ALL SELECT 'UY', 'Uruguay', 0
    UNION ALL SELECT 'UZ', 'Uzbekistan', 0
    UNION ALL SELECT 'VU', 'Vanuatu', 0
    UNION ALL SELECT 'VA', 'Vatican City', 0
    UNION ALL SELECT 'VE', 'Venezuela', 0
    UNION ALL SELECT 'VN', 'Vietnam', 0
    UNION ALL SELECT 'WF', 'Wallis & Futuna', 0
    UNION ALL SELECT 'EH', 'Western Sahara', 0
    UNION ALL SELECT 'YE', 'Yemen', 0
    UNION ALL SELECT 'ZM', 'Zambia', 0
    UNION ALL SELECT 'ZW', 'Zimbabwe', 0
);
