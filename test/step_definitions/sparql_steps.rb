When(/^I enter the wikitext:$/) do |wikiText|  
  on(EditPage).article_text = wikiText
end

Then(/^table should be there:$/) do |table|
   nodesTR = Nokogiri::HTML(@browser.html).css(".wikitable tr")
   tableResult = nodesTR.map {|tr| tr.css("th,td").map {|cell| cell.text.strip}}
#   p tableResult
#   p table.raw()
   table.diff!(tableResult)
end

Given /^I has a empty graph ([^ ]*) in the triplestore ([^ ]*)$/ do |graph,endpoint|
  c = Curl::Easy.http_delete("#{endpoint}/data/?graph=#{graph}")
  c.status.should match(/^.*200.*$/i)
end

Then(/^LinkedWiki's table should be there:$/) do |table|
   nodesTR = Nokogiri::HTML(@browser.html).css(".wikitable tr")
   tableResult = nodesTR.map {|tr| tr.css("th,td").map {|cell| cell.text.strip}}
   tableResult.delete(tableResult.last)
#   p tableResult
#   p table.raw()
   table.diff!(tableResult)
end

When(/^I do this SPARQL query in the triplestore ([^ ]*):$/) do |endpoint,query|  
  c = Curl::Easy.http_post("#{endpoint}/update/",
                         Curl::PostField.content('update', query))
  c.status.should match(/^.*200.*$/i)
end




