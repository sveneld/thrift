#
# Licensed to the Apache Software Foundation (ASF) under one
# or more contributor license agreements. See the NOTICE file
# distributed with this work for additional information
# regarding copyright ownership. The ASF licenses this file
# to you under the Apache License, Version 2.0 (the
# "License"); you may not use this file except in compliance
# with the License. You may obtain a copy of the License at
#
#   http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing,
# software distributed under the License is distributed on an
# "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
# KIND, either express or implied. See the License for the
# specific language governing permissions and limitations
# under the License.
#

stubs: $(THRIFT) ../v0.16/ThriftTest.thrift ../SmallTest.thrift
	$(THRIFT) --gen rb ../v0.16/ThriftTest.thrift
	$(THRIFT) --gen rb ../SmallTest.thrift
	$(THRIFT) --gen rb ../Recursive.thrift

precross: stubs

check: stubs
if HAVE_BUNDLER
	$(BUNDLER) install
	$(BUNDLER) exec $(RUBY) -I. test_suite.rb
endif

clean-local:
	$(RM) -r gen-rb/

distdir:
	$(MAKE) $(AM_MAKEFLAGS) distdir-am

dist-hook:
	$(RM) -r $(distdir)/gen-rb/
