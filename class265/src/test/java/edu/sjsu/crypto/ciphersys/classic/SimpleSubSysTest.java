package edu.sjsu.crypto.ciphersys.classic;

import static org.junit.jupiter.api.Assertions.*;

import org.junit.jupiter.api.Test;

class SimpleSubSysTest {

	@Test
	void test() {
		int key = 5;
		SimpleSubSys sys = new SimpleSubSys(key);
		String plaintext = "defend , the . ? east wall of the castle";
		System.out.println("Ciphertext = ['" + sys.encrypt(plaintext)+ "']");
		String ciphertext = "IJKJSI YMJ JFXY BFQQ TK YMJ HFXYQJ";
		System.out.println("Recovered Plaintext = [" + sys.decrypt(ciphertext)+ "]");
		
	}

}
