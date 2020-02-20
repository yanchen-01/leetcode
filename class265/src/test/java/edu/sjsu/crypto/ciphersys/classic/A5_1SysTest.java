package edu.sjsu.crypto.ciphersys.classic;

import static org.junit.jupiter.api.Assertions.*;

import org.junit.jupiter.api.Test;

class A5_1SysTest {

	@Test
	void test() {
		/*
		String plaintext = "Defend the east wall!";
		String ciphertext = "1df58522801a1789fa5c9d48486efedbc3c39acc05";
		String pass = "GoToHell#007";
		
		A5_1Sys sys = new A5_1Sys (pass);
		System.out.println("ciphertext = [" + sys.encrypt(plaintext)+ "]");
		System.out.println("Recovered Plaintext = [" + sys.decrypt(ciphertext)+ "]");
		*/
		
		
		String plaintext = "My name is sida, I like this class!";
		String ciphertext = "770c48ba9770f1d3139236d3b3442a75ca51e82ba63e6346f14011a9464d076547f18362e5";
		String pass = "NoDayOff";
		
		A5_1Sys sys = new A5_1Sys (pass);
		//System.out.println("ciphertext = [" + sys.encrypt(plaintext)+ "]");
		System.out.println("Recovered Plaintext = [" + sys.decrypt(ciphertext)+ "]");
		
	}

}
