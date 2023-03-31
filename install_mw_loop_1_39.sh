cd extensions
git clone https://github.com/oncampus/mediawiki-extensions-Loop.git Loop -b REL1_39
cd Loop
composer install
cd ../
git clone https://github.com/wikimedia/mediawiki-extensions-WikiEditor.git WikiEditor -b REL1_39
cd WikiEditor
composer install
npm install
cd ../
git clone https://github.com/oncampus/mediawiki-extensions-FlaggedRevs.git -b REL1_39 FlaggedRevs
cd FlaggedRevs
composer install
npm install
cd ../
git clone https://github.com/wikimedia/mediawiki-extensions-Math.git -b REL1_39 Math
cd Math
composer install
npm install
cd ../
git clone https://github.com/wikimedia/mediawiki-extensions-MsUpload.git -b REL1_39 MsUpload
cd MsUpload
composer install
npm install
cd ../
git clone https://github.com/wikimedia/mediawiki-extensions-SyntaxHighlight_GeSHi.git -b REL1_39 SyntaxHighlight_GeSHi
cd SyntaxHighlight_GeSHi
composer install
npm install
cd ../
git clone https://github.com/wikimedia/mediawiki-extensions-ImageMap -b REL1_39 ImageMap
cd ImageMap
composer install
npm install
cd ../
git clone https://github.com/wikimedia/mediawiki-extensions-Score.git -b REL1_39 Score
cd Score
composer install
npm install
cd ../
git clone https://github.com/StarCitizenWiki/mediawiki-extensions-EmbedVideo.git -b tags/v3.1.1 EmbedVideo
cd EmbedVideo
composer install
cd ../
git clone https://github.com/wikimedia/mediawiki-extensions-Quiz.git -b REL1_39 Quiz
cd Quiz
composer install
npm install
cd ../
git clone https://github.com/wikimedia/mediawiki-extensions-Cite.git -b REL1_39 Cite
cd Cite
composer install
npm install
cd ../
git clone https://github.com/wikimedia/mediawiki-extensions-Lingo.git -b REL1_39 Lingo
cd Lingo
composer install
npm install
cd ../
git clone https://github.com/wikimedia/mediawiki-extensions-ConfirmEdit -b REL1_39 ConfirmEdit
cd ConfirmEdit
composer install
npm install
cd ../
git clone https://github.com/wikimedia/mediawiki-extensions-Widgets -b REL1_39 Widgets
cd Widgets
composer install
npm install
cd ../../skins
git clone https://github.com/oncampus/mediawiki-skins-Loop.git Loop -b REL1_39
cd Loop
composer install
npm install
