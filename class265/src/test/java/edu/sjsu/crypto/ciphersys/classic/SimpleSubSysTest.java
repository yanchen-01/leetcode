package edu.sjsu.crypto.ciphersys.classic;

import static org.junit.jupiter.api.Assertions.*;

import org.junit.jupiter.api.Test;

class SimpleSubSysTest {

	@Test
	void SimpleSubSys() {
		int key = 15;
		SimpleSubSys sys = new SimpleSubSys(key);
		String plaintext = "my name is sida, I like this class!";
		System.out.println("Ciphertext = ['" + sys.encrypt(plaintext)+ "']");
		String ciphertext = "CD RAPHH RPCRTAAPIXDC";
		System.out.println("Recovered Plaintext = [" + sys.decrypt(ciphertext)+ "]");
	}

}
