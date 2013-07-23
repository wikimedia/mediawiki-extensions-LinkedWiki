export MEDIAWIKI_URL=http://127.0.0.1:8080/wiki/

cp -R * ~/git/browsertests/features/
cd ~/git/browsertests
bundle exec cucumber -v -b ./features/sparqlbasic.feature 
bundle exec cucumber -v -b ./features/table2CSV.feature
cd ~/git/vagrant/mediawiki/extensions/LinkedWiki/test/