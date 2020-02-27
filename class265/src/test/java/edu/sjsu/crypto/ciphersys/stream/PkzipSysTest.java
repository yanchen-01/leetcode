package edu.sjsu.crypto.ciphersys.stream;

import static org.junit.jupiter.api.Assertions.*;

import org.junit.jupiter.api.Test;

class PkzipSysTest {

	@Test
	void test() {
		String plaintext = "defend the east wall!";
		String pass = "GoToHell#007";
		String ciphertext = "756467646f653164786520756062753177616c7d30";
		
		PkzipSys sys = new PkzipSys (pass);
		System.out.println("ciphertext = [" + sys.encrypt(plaintext)+ "]");
		
		PkzipSys sysR = new PkzipSys (pass);
		System.out.println("Recovered Plaintext = [" + sysR.decrypt(ciphertext)+ "]");
	}

}

