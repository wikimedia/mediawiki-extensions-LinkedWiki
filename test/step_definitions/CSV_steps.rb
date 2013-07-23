When(/^I click link export CSV$/) do
    on(LinkedwikiTableBasic).CSV
end

When(/^I click link refresh$/) do
    on(LinkedwikiTableBasic).refresh
end

#When /^I confirm popup$/ do
#  @browser.driver.browser.switch_to.alert.accept    
#end

#When /^I dismiss popup$/ do
#  @browser.driver.browser.switch_to.alert.dismiss
#end

#Then(/^file CSV should be there:$/) do |table|
  # table is a Cucumber::Ast::Table
 # FasterCSV.foreach(@browser.html) do |row|
 #    puts row[0]
 # end
#end
