package edu.sjsu.crypto.ciphersys.classic;

import static org.junit.jupiter.api.Assertions.*;

import org.junit.jupiter.api.Test;

class HelloWorldTest {

	@Test
	void test() {
		HelloWorld.greeting();
	}
	
	@Test
	void testUsingCryptoUtil1() {
		HelloWorld.usingCryptoUtil1();
	}
	
	@Test
	void testUsingCryptoUtil2() {
		HelloWorld.usingCryptoUtil2();
	}

}
