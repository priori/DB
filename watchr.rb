require 'rbconfig'



def run_test()
	# file = "test/test.php"
	file = "test/test.php"
	#if (RbConfig::CONFIG['host_os'] = /mingw32/) then 
	#	system('cls')
	#else
	#	if (RbConfig::CONFIG['host_os'] =~ /cygwin/) then 
			system('clear')
	#	else
	#		 puts "\e[H\e[2J"
	#	end
	#end
	puts "Running #{file}"
	result = `phpunit ./#{file}`
	puts result
end


watch("src/.*.php") do |match|
  run_test
end

watch("test/.*.php") do |match|
  run_test
end

run_test
